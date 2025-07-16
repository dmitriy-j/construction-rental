<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Services\EquipmentAvailabilityService;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('lessee_company_id', auth()->user()->company_id)
            ->with(['lessorCompany', 'items.equipment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('lessee.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        // Явная проверка принадлежности заказа
        if ($order->lessee_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $order->load(['items.equipment', 'lessorCompany', 'lesseeCompany']);
        return view('lessee.orders.show', compact('order'));
    }

    public function cancel(Order $order)
    {
        // Проверка прав
        if ($order->lessee_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        // Проверка допустимых статусов для отмены
        $allowedStatuses = [Order::STATUS_PENDING, Order::STATUS_PENDING_APPROVAL, Order::STATUS_CONFIRMED];
        if (!in_array($order->status, $allowedStatuses)) {
            return back()->withErrors('Невозможно отменить заказ в текущем статусе');
        }

        $order->cancel();
        $order->user->notify(new \App\Notifications\OrderStatusChanged($order));

        return redirect()->back()->with('success', 'Заказ успешно отменен');
    }

    public function requestExtension(Order $order, Request $request)
    {
        // Проверка прав
        if ($order->lessee_company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен'
            ], 403);
        }

        // Валидация данных
        $validator = Validator::make($request->all(), [
            'new_end_date' => 'required|date|after:'.$order->end_date->format('Y-m-d')
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $service = app(EquipmentAvailabilityService::class);
        $newEndDate = $request->new_end_date;

        // Проверка доступности оборудования
        foreach ($order->items as $item) {
            if (!$service->isAvailable(
                $item->equipment,
                $order->end_date->addDay()->format('Y-m-d'),
                $newEndDate
            )) {
                return response()->json([
                    'success' => false,
                    'message' => "Оборудование {$item->equipment->title} недоступно на выбранные даты"
                ], 400);
            }
        }

        // Обновление заказа
        $order->update([
            'extension_requested' => true,
            'requested_end_date' => $newEndDate,
            'status' => Order::STATUS_EXTENSION_REQUESTED
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Запрос на продление отправлен арендодателю'
        ]);
    }
}
