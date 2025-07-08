<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\EquipmentAvailabilityService;

class LessorOrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('lessor_company_id', auth()->user()->company_id)
            ->with(['lesseeCompany', 'items.equipment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('lessor.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $order->load(['items.equipment', 'lesseeCompany']);
        return view('lessor.orders.show', compact('order'));
    }

    public function updateStatus(Order $order, Request $request)
    {
        $request->validate([
            'status' => 'required|in:confirmed,cancelled'
        ]);

        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
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

        $order->update(['status' => Order::STATUS_ACTIVE]);
        return back()->with('success', 'Заказ переведен в статус "Активный"');
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
}
