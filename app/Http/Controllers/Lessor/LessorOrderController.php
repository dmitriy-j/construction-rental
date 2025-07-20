<?php

namespace App\Http\Controllers\Lessor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DeliveryNote;
use Illuminate\Http\Request;
use App\Services\EquipmentAvailabilityService;
use App\Services\DeliveryNoteService;
use App\Services\DeliveryScenarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\PlatformDeliveryRequested;

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
            'items.deliveryNote.deliveryTo',
            'parentOrder'
        ]);

        $deliveryAddress = 'Не указан';
        if ($order->items->isNotEmpty() && $firstItem = $order->items->first()) {
            if ($firstItem->deliveryTo) {
                $deliveryAddress = $firstItem->deliveryTo->address;
            } elseif ($firstItem->deliveryNote && $firstItem->deliveryNote->deliveryTo) {
                $deliveryAddress = $firstItem->deliveryNote->deliveryTo->address;
            }
        }

        $lessorBaseAmount = $order->items->sum(function($item) {
            return $item->rentalTerm->price_per_hour * $item->period_count;
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
            'status' => 'required|in:confirmed,cancelled'
        ]);

        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

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

    public function approve(Request $request, Order $order)
    {
        if ($order->lessor_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $deliveryScenario = $request->input('delivery_scenario', 'none');

        DB::transaction(function() use ($order, $deliveryScenario, $request) {
            $order->update([
                'status' => Order::STATUS_CONFIRMED,
                'delivery_scenario' => $deliveryScenario,
                'confirmed_at' => now()
            ]);

            if ($order->delivery_type === Order::DELIVERY_DELIVERY) {
                $scenarioService = app(\App\Services\DeliveryScenarioService::class);

                foreach ($order->items as $item) {
                    $scenarioService->handleOrderConfirmation($item, $deliveryScenario);
                }

                if ($deliveryScenario === 'platform') {
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
            'delivery_notes.*.departure_time' => 'required|date'
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
            'rejection_reason' => 'required|string|max:500'
        ]);

        $order->update([
            'status' => Order::STATUS_REJECTED,
            'rejection_reason' => $request->rejection_reason,
            'rejected_at' => now()
        ]);

        app(EquipmentAvailabilityService::class)->releaseBooking($order);

        Log::info('Заказ отклонён', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'reason' => $request->rejection_reason
        ]);

        return back()->with('success', 'Заказ отклонен');
    }
}
