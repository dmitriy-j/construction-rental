<?php
// app/Http/Controllers/Admin/AdminOrderController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderRecalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminOrderController extends Controller
{
    protected $recalculationService;

    public function __construct(OrderRecalculationService $recalculationService)
    {
        $this->recalculationService = $recalculationService;
    }

    /**
     * Список всех заказов в админке
     */
    public function index(Request $request)
    {
        $query = Order::with([
            'lesseeCompany',
            'lessorCompany',
            'items.equipment',
            'childOrders.items' // ДОБАВЛЕНО: загружаем дочерние заказы с позициями
        ])
        ->whereNull('parent_order_id') // Только родительские заказы
        ->latest();

        // Фильтрация по статусу
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Фильтрация по дате
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20);

        // ПЕРЕСЧИТЫВАЕМ СУММЫ ДЛЯ АГРЕГИРОВАННЫХ ЗАКАЗОВ
        $orders->getCollection()->transform(function ($order) {
            if ($order->isParent() && $order->childOrders->count() > 0) {
                // Рассчитываем суммы из дочерних заказов
                $allItems = $order->childOrders->flatMap->items;

                $calculatedBaseAmount = $allItems->sum(function($item) {
                    return ($item->fixed_lessor_price ?? $item->base_price) * $item->period_count;
                });

                $calculatedPlatformFee = $allItems->sum('platform_fee');
                $calculatedTotalAmount = $allItems->sum('total_price');
                $calculatedDeliveryCost = $order->childOrders->sum('delivery_cost');

                // Добавляем вычисленные суммы как дополнительные атрибуты
                $order->calculated_base_amount = $calculatedBaseAmount;
                $order->calculated_total_amount = $calculatedTotalAmount;
                $order->calculated_platform_fee = $calculatedPlatformFee;
                $order->calculated_delivery_cost = $calculatedDeliveryCost;
                $order->calculated_lessor_payout = $calculatedBaseAmount + $calculatedDeliveryCost;
            } else {
                // Для обычных заказов используем существующие суммы
                $order->calculated_base_amount = $order->base_amount;
                $order->calculated_total_amount = $order->total_amount;
                $order->calculated_platform_fee = $order->platform_fee;
                $order->calculated_delivery_cost = $order->delivery_cost;
                $order->calculated_lessor_payout = $order->lessor_payout;
            }

            return $order;
        });

        // Статистика для фильтров
        $statuses = Order::statuses();
        $totalOrders = Order::whereNull('parent_order_id')->count();
        $pendingOrders = Order::whereNull('parent_order_id')
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PENDING_APPROVAL])
            ->count();
        $activeOrders = Order::whereNull('parent_order_id')
            ->where('status', Order::STATUS_ACTIVE)
            ->count();
        $completedOrders = Order::whereNull('parent_order_id')
            ->where('status', Order::STATUS_COMPLETED)
            ->count();

        return view('admin.orders.index', compact(
            'orders',
            'statuses',
            'totalOrders',
            'pendingOrders',
            'activeOrders',
            'completedOrders'
        ));
    }

    public function show(Order $order)
    {
        // Проверяем что заказ принадлежит арендатору
        abort_unless($order->lesseeCompany->is_lessee, 404);

        $order->load([
            'items.equipment.company',
            'items.rentalTerm',
            'items.rentalCondition',
            'lessorCompany',
            'lesseeCompany',
            'waybills',
            'deliveryNote',
            'childOrders.items.equipment.company',
            'childOrders.items.rentalTerm',
            'childOrders.lessorCompany',
        ]);

        // Собираем ВСЕ позиции (из родительского + дочерних заказов)
        $allItems = collect();

        if ($order->isParent()) {
            // Для родительского заказа берем позиции из всех дочерних
            $allItems = $order->childOrders->flatMap->items;
        } else {
            // Для дочернего заказа берем его собственные позиции
            $allItems = $order->items;
        }

        // Рассчитываем финансовые показатели
        $calculatedBaseAmount = $allItems->sum(function($item) {
            return ($item->fixed_lessor_price ?? $item->base_price) * $item->period_count;
        });

        $calculatedPlatformFee = $allItems->sum('platform_fee');
        $calculatedTotalAmount = $allItems->sum('total_price');

        return view('admin.orders.show', compact(
            'order',
            'allItems',
            'calculatedBaseAmount',
            'calculatedPlatformFee',
            'calculatedTotalAmount'
        ));
    }

    /**
     * Форма изменения дат заказа
     */
    public function editDates(Order $order)
    {
        return view('admin.orders.edit_dates', compact('order'));
    }

    /**
     * Проверка доступности оборудования на новые даты
     */
    public function checkDatesAvailability(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $availability = $this->recalculationService->checkAvailability(
            $order,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json($availability);
    }

    /**
     * Обновление дат заказа с пересчетом
     */
    public function updateDates(Request $request, Order $order)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'confirm_availability' => 'sometimes|boolean',
        ]);

        // Если не подтверждена доступность - проверяем
        if (!$request->confirm_availability) {
            $availability = $this->recalculationService->checkAvailability(
                $order,
                Carbon::parse($request->start_date),
                Carbon::parse($request->end_date)
            );

            if (!$availability['available']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'dates' => 'Оборудование недоступно на выбранные даты: ' .
                            collect($availability['unavailable_equipment'])
                                ->pluck('equipment')
                                ->implode(', ')
                    ])
                    ->with('availability_check', $availability);
            }
        }

        $result = $this->recalculationService->recalculateOrderDates(
            $order,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Даты заказа успешно изменены. Суммы пересчитаны.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $result['message']);
    }

    /**
     * Принудительное обновление дат без проверки доступности
     */
    public function forceUpdateDates(Request $request, Order $order)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $result = $this->recalculationService->recalculateOrderDates(
            $order,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Даты заказа принудительно изменены. Суммы пересчитаны.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $result['message']);
    }

    /**
     * Подтверждение заказа (для платформенной техники — платформа подтверждает сама)
     */
    public function confirm(Order $order)
    {
        $allowed = [Order::STATUS_PENDING, Order::STATUS_PENDING_APPROVAL, Order::STATUS_AGGREGATED];
        if (!in_array($order->status, $allowed)) {
            return redirect()->back()->with('error', 'Заказ нельзя подтвердить в текущем статусе');
        }

        \DB::beginTransaction();
        try {
            $this->setOrderStatus($order, Order::STATUS_CONFIRMED, 'Подтвержден администратором');

            // Для родительского заказа подтверждаем всех детей
            if ($order->isParent()) {
                foreach ($order->childOrders as $childOrder) {
                    $this->setOrderStatus($childOrder, Order::STATUS_CONFIRMED, 'Подтвержден администратором');
                }
            }

            $order->confirmed_at = now();
            $order->save();

            // Уведомляем арендатора
            if ($order->user) {
                $order->user->notify(new \App\Notifications\OrderApproved($order));
            }

            \DB::commit();

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Заказ #' . $order->id . ' подтвержден');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Order confirm error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ошибка подтверждения заказа: ' . $e->getMessage());
        }
    }

    /**
     * Отклонение заказа с указанием причины
     */
    public function reject(Request $request, Order $order)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        \DB::beginTransaction();
        try {
            $this->setOrderStatus($order, Order::STATUS_REJECTED, 'Отклонен администратором: ' . $request->rejection_reason);

            // Для родительского заказа отклоняем всех детей
            if ($order->isParent()) {
                foreach ($order->childOrders as $childOrder) {
                    $this->setOrderStatus($childOrder, Order::STATUS_REJECTED, 'Отклонен администратором: ' . $request->rejection_reason);
                }
            }

            $order->rejection_reason = $request->rejection_reason;
            $order->rejected_at = now();
            $order->save();

            // Уведомляем арендатора
            if ($order->user) {
                $order->user->notify(new \App\Notifications\OrderRejected($order, $request->rejection_reason));
            }

            \DB::commit();

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Заказ #' . $order->id . ' отклонен');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Order reject error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ошибка отклонения заказа: ' . $e->getMessage());
        }
    }

    /**
     * Универсальная смена статуса заказа (active/completed/cancelled)
     */
    public function setStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:active,completed,cancelled',
        ]);

        $status = $request->status;
        $notes = $request->input('notes', '');

        \DB::beginTransaction();
        try {
            $this->setOrderStatus($order, $status, $notes ?: 'Статус изменен администратором');

            // Для родительского заказа — меняем и детей
            if ($order->isParent()) {
                foreach ($order->childOrders as $childOrder) {
                    $this->setOrderStatus($childOrder, $status, $notes ?: 'Статус изменен администратором');
                }
            }

            // Если завершаем заказ — записываем дату завершения
            if ($status === Order::STATUS_COMPLETED && method_exists($order, 'complete')) {
                $order->complete();
            } else {
                $order->save();
            }

            // Уведомляем арендатора об изменении статуса
            if ($order->user) {
                $order->user->notify(new \App\Notifications\OrderStatusChanged($order));
            }

            \DB::commit();

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Статус заказа #' . $order->id . ' изменен на ' . Order::statusText($status));
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Order setStatus error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ошибка изменения статуса: ' . $e->getMessage());
        }
    }

    /**
     * Запись в историю статусов заказа
     */
    protected function setOrderStatus(Order $order, string $status, string $notes = ''): void
    {
        $oldStatus = $order->status;
        $order->status = $status;
        $order->save();

        \DB::table('order_status_histories')->insert([
            'order_id' => $order->id,
            'status' => $status,
            'changed_by' => auth()->id(),
            'notes' => $notes ?: ($oldStatus . ' -> ' . $status),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
