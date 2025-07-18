<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Services\EquipmentAvailabilityService;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('lessee_company_id', auth()->user()->company_id)
            ->whereNull('parent_order_id')
            ->with([
                'childOrders.items', // Загружаем дочерние заказы и их позиции
                'items' // Загружаем позиции для обычных заказов
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $orders->getCollection()->transform(function ($order) {
            // Рассчитываем общую стоимость
            if ($order->isParent()) {
                $order->calculated_total = $order->childOrders->sum(function ($childOrder) {
                    return $childOrder->items->sum(function ($item) {
                        return ($item->price_per_unit * $item->period_count) + ($item->delivery_cost ?? 0);
                    });
                });
            } else {
                $order->calculated_total = $order->items->sum(function ($item) {
                    return ($item->price_per_unit * $item->period_count) + ($item->delivery_cost ?? 0);
                });
            }

            // Рассчитываем количество позиций
            $order->total_items_count = $order->isParent()
                ? $order->childOrders->sum(fn($child) => $child->items->count())
                : $order->items->count();

            return $order;
        });

        return view('lessee.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        if ($order->lessee_company_id !== auth()->user()->company_id || $order->isChild()) {
            abort(403);
        }

        // Для родительских заказов
        if ($order->isParent()) {
            $order->load([
                'childOrders.items.equipment.mainImage',
                'childOrders.items.equipment.company',
                'childOrders.items.deliveryNote',
                'childOrders.lessorCompany'
            ]);
        }
        // Для дочерних заказов
        else {
            $order->load([
                'items.equipment.mainImage',
                'items.equipment.company',
                'items.deliveryNote',
                'lessorCompany'
            ]);
        }

        $allItems = $order->isParent()
        ? $order->childOrders->flatMap->items
        : $order->items;

        // Пересчитываем суммы по простому принципу: (цена за час * часы) + доставка
        $allItems->each(function ($item) {
            // Используем price_per_unit как окончательную стоимость часа
            $item->simple_rental_total = $item->price_per_unit * $item->period_count;
            $item->simple_total = $item->simple_rental_total + $item->delivery_cost;
        });

        // Общая сумма заказа - сумма простых итогов по позициям
        $simpleGrandTotal = $allItems->sum('simple_total');

        return view('lessee.orders.show', compact(
            'order',
            'allItems',
            'simpleGrandTotal'
        ));
    }

    public function cancel(Order $order)
    {
        if ($order->lessee_company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $allowedStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PENDING_APPROVAL,
            Order::STATUS_CONFIRMED
        ];

        if (!in_array($order->status, $allowedStatuses)) {
            return back()->withErrors('Невозможно отменить заказ в текущем статусе');
        }

        try {
            $order->load('items.equipment');
            $order->cancel();

            $order->user->notify(new OrderStatusChanged($order));

            return back()->with('success', 'Заказ успешно отменен');
        } catch (\Exception $e) {
            Log::error('Ошибка отмены заказа: '.$e->getMessage());
            return back()->withErrors($e->getMessage());
        }
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
