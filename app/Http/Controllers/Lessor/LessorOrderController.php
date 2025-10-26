<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\Equipment;
use App\Models\Operator;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\RentalCondition;
use App\Models\Waybill;
use App\Notifications\OrderActivatedAdminNotification;
use App\Notifications\OrderActivatedNotification;
use App\Services\DeliveryNoteService;
use App\Services\DeliveryScenarioService;
use App\Services\EquipmentAvailabilityService;
use App\Services\UpdProcessingService;
use App\Services\WaybillCreationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessorOrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $companyId = auth()->user()->company_id;

        $orders = Order::where('lessor_company_id', $companyId)
            ->whereNotNull('parent_order_id')
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->with(['items.equipment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('lessor.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        if ($order->lessor_company_id !== auth()->user()->company_id || $order->isParent()) {
            abort(403);
        }

        $order->load([
            'items.equipment',
            'items.rentalTerm',
            'items.deliveryNote.deliveryTo',
            'items.equipment.activeOperator',
            'parentOrder',
        ]);

        $deliveryAddress = 'Не указан';
        if ($order->items->isNotEmpty() && $firstItem = $order->items->first()) {
            if ($firstItem->deliveryTo) {
                $deliveryAddress = $firstItem->deliveryTo->address;
            } elseif ($firstItem->deliveryNote && $firstItem->deliveryNote->deliveryTo) {
                $deliveryAddress = $firstItem->deliveryNote->deliveryTo->address;
            }
        }

        // ПРАВИЛЬНЫЙ расчет: используем фиксированную цену арендодателя из позиций
        $lessorBaseAmount = $order->items->sum(function ($item) {
            // Важно: используем fixed_lessor_price из OrderItem!
            return $item->fixed_lessor_price * $item->period_count;
        });

        $orderDetails = [
            'shift_hours' => $order->shift_hours,
            'shifts_per_day' => $order->shifts_per_day,
            'total_hours' => $order->items->sum('period_count'),
            'payment_type' => $order->payment_type,
            'transportation' => $order->transportation,
            'fuel_responsibility' => $order->fuel_responsibility,
            'delivery_address' => $deliveryAddress,
            'lessor_base_amount' => $lessorBaseAmount,
            'delivery_cost' => $order->delivery_cost,
            'total_payout' => $lessorBaseAmount + $order->delivery_cost,

        ];

        return view('lessor.orders.show', compact('order', 'orderDetails'));
    }

    public function updateStatus(Order $order, Request $request)
    {
        $request->validate([
            'status' => 'required|in:confirmed,cancelled',
        ]);

        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $allowedStatuses = [Order::STATUS_PENDING_APPROVAL, Order::STATUS_CONFIRMED];
        if (! in_array($order->status, $allowedStatuses)) {
            return back()->withErrors('Невозможно изменить статус заказа в текущем состоянии');
        }

        $order->update(['status' => $request->status]);

        if ($request->status === Order::STATUS_CANCELLED) {
            app(EquipmentAvailabilityService::class)->releaseBooking($order);
        }

        $order->user->notify(new \App\Notifications\OrderStatusChanged($order));

        return back()->with('success', 'Статус заказа обновлен');
    }

    public function markAsActive(Order $order)
    {
        // Проверка прав доступа
        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        // Разрешенные статусы для активации
        $allowedStatuses = [Order::STATUS_CONFIRMED, Order::STATUS_IN_DELIVERY];
        if (! in_array($order->status, $allowedStatuses)) {
            return back()->withErrors('Невозможно начать аренду в текущем статусе');
        }

        // Проверка даты начала аренды
        if (now()->lt($order->start_date)) {
            return back()->withErrors(
                'Нельзя начать аренду раньше '.$order->start_date->format('d.m.Y')
            );
        }

        DB::transaction(function () use ($order) {
            $rentalCondition = $order->rentalCondition;
            $shiftsPerDay = $rentalCondition->shifts_per_day ?? 1;
            $missingOperators = [];

            // Проверка наличия активных операторов
            $order->load(['items.equipment.operators' => function ($query) {
                $query->where('is_active', true);
            }]);

            foreach ($order->items as $item) {
                $equipment = $item->equipment;

                // Проверяем наличие активного дневного оператора
                $hasDayOperator = $equipment->operators
                    ->where('shift_type', Operator::SHIFT_DAY)
                    ->isNotEmpty();

                if (! $hasDayOperator) {
                    $missingOperators[] = $equipment->title.' (дневная смена)';
                }

                // Проверяем ночную смену если требуется
                if ($shiftsPerDay > 1) {
                    $hasNightOperator = $equipment->operators
                        ->where('shift_type', Operator::SHIFT_NIGHT)
                        ->isNotEmpty();

                    if (! $hasNightOperator) {
                        $missingOperators[] = $equipment->title.' (ночная смена)';
                    }
                }
            }

            if (! empty($missingOperators)) {
                throw new \Exception('Для оборудования не назначены активные операторы: '.implode(', ', $missingOperators));
            }

            // Обновление статуса заказа
            $order->update([
                'status' => Order::STATUS_ACTIVE,
                'service_start_date' => now(),
            ]);

            // Создание ТОЛЬКО ПЕРВЫХ путевых листов
            $service = new WaybillCreationService;
            $service->createForOrder($order);

            // Запись в историю статусов
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => Order::STATUS_ACTIVE,
                'changed_by' => auth()->id(),
                'notes' => 'Аренда активирована, созданы начальные путевые листы',
            ]);

            // Отправка уведомлений
            $this->sendActivationNotifications($order);

            Log::info('Order activated with initial waybills', [
                'order_id' => $order->id,
                'waybill_count' => $order->waybills()->count(),
            ]);
        });

        return back()->with('success', 'Аренда успешно начата. Созданы начальные путевые листы.');
    }

    public function markAsCompleted(Order $order)
    {
        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $order->update(['status' => Order::STATUS_COMPLETED]);

        return back()->with('success', 'Заказ завершен');
    }

    public function handleExtension(Order $order, Request $request)
    {
        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'price_adjustment' => 'nullable|numeric|min:0',
        ]);

        $service = app(EquipmentAvailabilityService::class);

        if ($request->action === 'approve') {
            foreach ($order->items as $item) {
                $service->bookEquipment(
                    $item->equipment,
                    $order->end_date->addDay()->format('Y-m-d'),
                    $order->requested_end_date,
                    $order->id,
                    'booked'
                );
            }

            $order->update([
                'end_date' => $order->requested_end_date,
                'total_amount' => $order->total_amount + ($request->price_adjustment ?? 0),
                'status' => Order::STATUS_CONFIRMED,
                'extension_requested' => false,
                'requested_end_date' => null,
            ]);

            return back()->with('success', 'Продление аренды подтверждено');
        } else {
            $order->update([
                'extension_requested' => false,
                'requested_end_date' => null,
                'status' => Order::STATUS_CONFIRMED,
            ]);

            return back()->with('success', 'Запрос на продление отклонен');
        }
    }

    public function approve(Request $request, Order $order)
    {
        \Log::info('[ORDER APPROVAL] Начало подтверждения заказа', [
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'input' => $request->all(),
            'delivery_type' => $order->delivery_type,
            'items_count' => $order->items->count(),
            'delivery_items' => $order->items->filter(fn ($i) => $i->delivery_to_id)->count(),
        ]);

        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $request->validate([
            'delivery_scenario' => 'required|in:lessor,platform,none',
        ]);

        $deliveryScenario = $request->input('delivery_scenario', 'none');

        \Log::debug('[ORDER APPROVAL] Выбранный сценарий', [
            'scenario' => $deliveryScenario,
        ]);

        DB::transaction(function () use ($order, $deliveryScenario) {
            $order->update([
                'status' => Order::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ]);

            \Log::info('[ORDER APPROVAL] Статус заказа обновлен');

            if ($order->delivery_type === Order::DELIVERY_DELIVERY) {
                \Log::debug('[ORDER APPROVAL] Обработка доставки');

                foreach ($order->items as $item) {
                    if (! $item->delivery_from_id || ! $item->delivery_to_id) {
                        throw new \Exception("Для позиции #{$item->id} не указаны адреса доставки");
                    }
                }

                // Используем DeliveryNoteService вместо DeliveryScenarioService
                $noteService = app(\App\Services\DeliveryNoteService::class);

                foreach ($order->items as $item) {
                    \Log::debug('[ORDER APPROVAL] Обработка позиции', [
                        'item_id' => $item->id,
                    ]);

                    if ($deliveryScenario === 'lessor') {
                        // Создаем ТН для арендодателя
                        $note = $noteService->createForOrderItem($item, DeliveryNote::TYPE_LESSOR_TO_PLATFORM);

                        \Log::info('[ORDER APPROVAL] Создана накладная для арендодателя', [
                            'delivery_note_id' => $note->id,
                            'type' => $note->type,
                            'sender' => $note->sender_company_id,
                            'receiver' => $note->receiver_company_id,
                        ]);
                    }
                }

                if ($deliveryScenario === 'platform') {
                    \Log::info('[ORDER APPROVAL] Инициирование поиска перевозчика');
                    event(new \App\Events\PlatformDeliveryRequested($order));
                }
            }
        });

        return back()->with('success', 'Заказ подтвержден');
    }

    public function prepareForShipment(Order $order, Request $request)
    {
        $request->validate([
            'delivery_notes' => 'required|array',
            'delivery_notes.*.id' => 'required|exists:delivery_notes,id',
            'delivery_notes.*.driver_name' => 'required|string',
            'delivery_notes.*.vehicle_model' => 'required|string',
            'delivery_notes.*.vehicle_number' => 'required|string',
            'delivery_notes.*.driver_contact' => 'required|string',
            'delivery_notes.*.departure_time' => 'required|date',
        ]);

        $service = app(DeliveryNoteService::class);

        foreach ($request->delivery_notes as $noteData) {
            $note = DeliveryNote::find($noteData['id']);
            $service->completeDeliveryNote($note, $noteData);
        }

        $order->update(['status' => Order::STATUS_PREPARED_FOR_SHIPMENT]);

        return back()->with('success', 'Данные для доставки успешно сохранены');
    }

    public function reject(Order $order, Request $request)
    {
        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $order->update([
            'status' => Order::STATUS_REJECTED,
            'rejection_reason' => $request->rejection_reason,
            'rejected_at' => now(),
        ]);

        app(EquipmentAvailabilityService::class)->releaseBooking($order);

        Log::info('Заказ отклонён', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Заказ отклонен');
    }

    protected function createWaybills(Order $order)
    {
        $rentalCondition = $order->rentalCondition;
        if (! $rentalCondition) {
            Log::error('Rental condition not found', ['order_id' => $order->id]);

            return;
        }

        $waybills = [];
        $now = now();

        foreach ($order->items as $item) {
            $equipment = $item->equipment;
            $equipment->load('operator');
            $operator = $equipment->operator;

            if (! $operator) {
                Log::warning('No operator assigned for equipment', [
                    'equipment_id' => $equipment->id,
                    'order_id' => $order->id,
                ]);
                event(new OperatorMissing($equipment, $order));

                continue;
            }

            // Рассчитываем стандартное потребление топлива
            $fuelConsumption = $this->calculateFuelConsumption($equipment, $rentalCondition);

            for ($shift = 0; $shift < $rentalCondition->shifts_per_day; $shift++) {
                $waybills[] = [
                    'order_id' => $order->id,
                    'equipment_id' => $equipment->id,
                    'operator_id' => $operator->id,
                    'rental_condition_id' => $rentalCondition->id,
                    'work_date' => $now,
                    'shift' => $shift == 0 ? Waybill::SHIFT_DAY : Waybill::SHIFT_NIGHT,
                    'status' => Waybill::STATUS_CREATED,

                    // Обязательные поля со значениями по умолчанию
                    'hours_worked' => 0,
                    'odometer_start' => 0,
                    'odometer_end' => 0,
                    'fuel_start' => 0,
                    'fuel_end' => 0,
                    'fuel_consumption_standard' => $fuelConsumption,
                    'fuel_consumption_actual' => 0,

                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($waybills)) {
            Waybill::insert($waybills);
        }
    }

    private function calculateFuelConsumption(Equipment $equipment, RentalCondition $condition): float
    {
        // Приоритет: спецификация оборудования > условие аренды > значение по умолчанию
        $fuelRate = $equipment->getNumericSpecValue('fuel_consumption')
                    ?? $condition->fuel_consumption_rate
                    ?? 10; // Л/час по умолчанию

        $shiftHours = $condition->shift_hours ?? 8; // Часов в смену по умолчанию

        return $fuelRate * $shiftHours;
    }

    protected function sendActivationNotifications(Order $order)
    {
        // Уведомление арендатору
        $order->user->notify(new OrderActivatedNotification($order));

        // Уведомление администратору платформы
        $adminUsers = \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->get();

        \Illuminate\Support\Facades\Notification::send(
            $adminUsers,
            new OrderActivatedAdminNotification($order)
        );
    }

    public function uploadUpd(Order $order, Request $request, UpdProcessingService $updService)
    {
        $request->validate([
            'upd_file' => 'required|file|mimes:xlsx,xls,pdf|max:10240',
            'number' => 'required|string',
            'issue_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            $upd = $updService->processUploadedUpd(
                $order,
                $request->file('upd_file'),
                $request->only(['number', 'issue_date', 'amount'])
            );

            return redirect()->back()
                ->with('success', 'УПД успешно загружен и ожидает проверки');

        } catch (\Exception $e) {
            Log::error('Ошибка загрузки УПД', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Ошибка загрузки УПД: '.$e->getMessage());
        }
    }
}
