<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Services\EquipmentAvailabilityService;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('lessee_company_id', auth()->user()->company_id)
            ->whereNull('parent_order_id')
            ->with(['childOrders.items', 'items'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $orders->getCollection()->transform(function ($order) {
            // Правильный расчет стоимости аренды
            $order->rental_amount = $order->isParent()
                ? $order->childOrders->sum('lessor_base_amount')
                : $order->lessor_base_amount;

            $order->delivery_amount = $order->delivery_cost;
            $order->calculated_total = $order->rental_amount + $order->delivery_amount;

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
                'childOrders.items.equipment.availabilityStatus',
                'childOrders.items.equipment.mainImage',
                'childOrders.items.equipment.company',
                'childOrders.items.deliveryNote',
                'childOrders.items.deliveryFrom',
                'childOrders.items.deliveryTo',
                'childOrders.lessorCompany'
            ]);
        }
        // Для дочерних заказов
        else {
            $order->load([
                'childOrders.items.equipment.availabilityStatus',
                'items.equipment.mainImage',
                'items.equipment.company',
                'items.deliveryNote',
                'items.deliveryFrom',
                'items.deliveryTo',
                'lessorCompany'
            ]);
        }

        $allItems = $order->isParent()
        ? $order->childOrders->flatMap->items
        : $order->items;

        // Пересчитываем суммы по простому принципу: (цена за час * часы) + доставка
        $allItems->each(function ($item) {
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
            Order::STATUS_CONFIRMED,
            Order::STATUS_AGGREGATED
        ];

        if (!in_array($order->status, $allowedStatuses)) {
            return back()->with('error', 'Невозможно отменить заказ в текущем статусе: ' . $order->status_text);
        }

        try {
            $order->cancel();

            // Отправляем уведомление только для родительского заказа
            if ($order->isParent()) {
                $order->user->notify(new OrderStatusChanged($order));
            }

            return back()->with('success', 'Заказ и все связанные подзаказы успешно отменены');
        } catch (\Exception $e) {
            Log::error('Ошибка отмены заказа: '.$e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Ошибка при отмене заказа: ' . $e->getMessage());
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

    /**
     * Создание нового заказа (добавлен метод store)
     */
    public function store(Request $request)
    {
        $request->validate([
            // ... другие поля ...
            'delivery_type' => 'required|in:pickup,delivery',
        ]);

        DB::transaction(function() use ($request) {
            $order = new Order();
            $order->lessee_company_id = auth()->user()->company_id;
            $order->status = Order::STATUS_PENDING;
            $order->delivery_type = $request->delivery_type;

            // Заполнение других полей заказа
            $order->fill($request->only([
                'start_date',
                'end_date',
                'delivery_address',
                'notes',
                // другие поля
            ]));

            $order->save();

            // Добавление элементов заказа
            foreach ($request->items as $itemData) {
                $order->items()->create([
                    'equipment_id' => $itemData['equipment_id'],
                    'quantity' => $itemData['quantity'],
                    'period_count' => $itemData['period_count'],
                    'delivery_from_id' => $itemData['delivery_from_id'] ?? null,
                    'delivery_to_id' => $itemData['delivery_to_id'] ?? null,
                    // другие поля элемента
                ]);
            }
        });

        return redirect()->route('lessee.orders.show', $order)
            ->with('success', 'Заказ успешно создан');
    }
}
