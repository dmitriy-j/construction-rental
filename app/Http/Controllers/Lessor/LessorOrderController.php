<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller; // Добавлен импорт базового контроллера
use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\EquipmentAvailabilityService;
use App\Notifications\OrderApproved;
use App\Notifications\OrderRejected;
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
        // Проверка прав
        if ($order->lessor_company_id !== auth()->user()->company_id || $order->isParent()) {
            abort(403);
        }

        $order->load([
            'items.equipment',
            'items.deliveryNote.deliveryTo', // Добавляем загрузку адреса доставки
            'parentOrder'
        ]);

        // Получаем адрес доставки из первой позиции заказа
        $deliveryAddress = 'Не указан';
        if ($order->items->isNotEmpty() && $firstItem = $order->items->first()) {
            if ($firstItem->deliveryNote && $firstItem->deliveryNote->deliveryTo) {
                $deliveryAddress = $firstItem->deliveryNote->deliveryTo->address;
            }
        }

        // Добавлен расчет деталей заказа
        $orderDetails = [
            'shift_hours' => $order->shift_hours,
            'shifts_per_day' => $order->shifts_per_day,
            'total_hours' => $order->items->sum('period_count'),
            'payment_type' => $order->payment_type,
            'transportation' => $order->transportation,
            'fuel_responsibility' => $order->fuel_responsibility,
            'delivery_address' => $deliveryAddress, // Добавляем адрес доставки

            // Финансовые данные
            'lessor_base_amount' => $order->lessor_base_amount,
            'delivery_cost' => $order->delivery_cost,
            'total_payout' => $order->lessor_base_amount + $order->delivery_cost,
        ];

        return view('lessor.orders.show', compact('order', 'orderDetails'));
    }

    public function updateStatus(Order $order, Request $request)
    {
        $request->validate([
            'status' => 'required|in:confirmed,cancelled'
        ]);

        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        // Добавляем проверку на допустимые статусы
        $allowedStatuses = [Order::STATUS_PENDING_APPROVAL, Order::STATUS_CONFIRMED];
        if (!in_array($order->status, $allowedStatuses)) {
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
        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        if (!$order->canBeActivated()) {
            $error = $order->status !== Order::STATUS_CONFIRMED
                ? 'Заказ должен быть в статусе "Подтвержден"'
                : 'Нельзя начать аренду раньше '. $order->activationAvailableDate();

            return back()->withErrors($error);
        }

        $order->update([
            'status' => Order::STATUS_ACTIVE,
            'service_start_date' => now()
        ]);

        return back()->with('success', 'Аренда успешно начата');
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
            'price_adjustment' => 'nullable|numeric|min:0'
        ]);

        $service = app(EquipmentAvailabilityService::class);

        if ($request->action === 'approve') {
            // Бронируем дополнительный период
            foreach ($order->items as $item) {
                $service->bookEquipment(
                    $item->equipment,
                    $order->end_date->addDay()->format('Y-m-d'),
                    $order->requested_end_date,
                    $order->id,
                    'booked'
                );
            }

            // Обновляем заказ
            $order->update([
                'end_date' => $order->requested_end_date,
                'total_amount' => $order->total_amount + ($request->price_adjustment ?? 0),
                'status' => Order::STATUS_CONFIRMED,
                'extension_requested' => false,
                'requested_end_date' => null
            ]);

            return back()->with('success', 'Продление аренды подтверждено');
        } else {
            $order->update([
                'extension_requested' => false,
                'requested_end_date' => null,
                'status' => Order::STATUS_CONFIRMED
            ]);

            return back()->with('success', 'Запрос на продление отклонен');
        }
    }

    // Обновленные методы подтверждения заказа
    public function approve(Order $order)
    {
        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        // Проверяем условия аренды
        $service = app(EquipmentAvailabilityService::class);
        if (!$service->validateRentalConditions($order)) {
            return back()->withErrors('Условия аренды не соблюдены');
        }

        // Обновляем статус заказа
        $order->update([
            'status' => Order::STATUS_CONFIRMED,
            'confirmed_at' => now()
        ]);

        // Временная замена уведомления на логирование
        Log::info('Заказ подтверждён', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'message' => 'Уведомление OrderApproved было заменено на логирование'
        ]);

        return back()->with('success', 'Заказ подтвержден');
    }

    public function reject(Order $order, Request $request)
    {
        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        // Обновляем статус заказа
        $order->update([
            'status' => Order::STATUS_REJECTED,
            'confirmed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
            'rejected_at' => now()
        ]);

        // Освобождаем оборудование
        app(EquipmentAvailabilityService::class)->releaseBooking($order);

        // Временная замена уведомления на логирование
        Log::info('Заказ отклонён', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'reason' => $request->rejection_reason,
            'message' => 'Уведомление OrderRejected было заменено на логирование'
        ]);

        return back()->with('success', 'Заказ отклонен');
    }

    public function approveOrder(Order $order)
    {
        $order->user->notify(new OrderApproved($order));
        // Проверка прав арендодателя
        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        DB::transaction(function() use ($order) {
            // Подтверждение заказа
            $order->update([
                'status' => Order::STATUS_CONFIRMED,
                'confirmed_at' => now()
            ]);

            // Бронирование доставки
            app(EquipmentAvailabilityService::class)->bookDelivery($order);
        });

        return redirect()->back()->with('success', 'Заказ подтвержден');
    }

}
