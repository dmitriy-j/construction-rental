<?php

namespace App\Http\Controllers\Lessee;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\OrderStatusChanged;
use App\Services\EquipmentAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $order->rental_amount = $order->isParent()
                ? $order->childOrders->sum('lessor_base_amount')
                : $order->lessor_base_amount;

            $order->delivery_amount = $order->delivery_cost;
            $order->calculated_total = $order->rental_amount + $order->delivery_amount;

            $order->total_items_count = $order->isParent()
                ? $order->childOrders->sum(fn ($child) => $child->items->count())
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

        if ($order->isParent()) {
            $order->load([
                'childOrders.items.equipment.availabilityStatus',
                'childOrders.items.equipment.mainImage',
                'childOrders.items.equipment.company',
                'childOrders.items.deliveryNote',
                'childOrders.items.deliveryFrom',
                'childOrders.items.deliveryTo',
                'childOrders.lessorCompany',
            ]);
        } else {
            $order->load([
                'items.equipment.mainImage',
                'items.equipment.company',
                'items.deliveryNote' => function ($query) {
                    $query->where('visible_to_lessee', true); // Только видимые ТН
                },
                'items.deliveryFrom',
                'items.deliveryTo',
                'lessorCompany',
            ]);
        }

        $allItems = $order->isParent()
            ? $order->childOrders->flatMap->items
            : $order->items;

        $allItems->each(function ($item) {
            // Заменяем price_per_unit на фиксированную стоимость
            $item->price_per_unit = $item->fixed_customer_price ?? $item->price_per_unit;

            // Пересчитываем суммы
            $item->simple_rental_total = $item->price_per_unit * $item->period_count;
            $item->simple_total = $item->simple_rental_total + $item->delivery_cost;

            // Перенесено внутрь callback-функции!
            $item->status_text = match ($item->status) {
                OrderItem::STATUS_PENDING => 'Ожидает',
                OrderItem::STATUS_IN_DELIVERY => 'В пути',
                OrderItem::STATUS_ACTIVE => 'Активна',
                OrderItem::STATUS_COMPLETED => 'Завершена',
                default => $item->status,
            };

            $item->status_color = match ($item->status) {
                OrderItem::STATUS_PENDING => 'warning',
                OrderItem::STATUS_IN_DELIVERY => 'info',
                OrderItem::STATUS_ACTIVE => 'success',
                OrderItem::STATUS_COMPLETED => 'secondary',
                default => 'light',
            };
        });

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
            Order::STATUS_AGGREGATED,
        ];

        if (! in_array($order->status, $allowedStatuses)) {
            return back()->with('error', 'Невозможно отменить заказ в текущем статусе: '.$order->status_text);
        }

        try {
            $order->cancel();

            if ($order->isParent()) {
                $order->user->notify(new OrderStatusChanged($order));
            }

            return back()->with('success', 'Заказ и все связанные подзаказы успешно отменены');
        } catch (\Exception $e) {
            Log::error('Ошибка отмены заказа: '.$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Ошибка при отмене заказа: '.$e->getMessage());
        }
    }

    public function requestExtension(Order $order, Request $request)
    {
        if ($order->lessee_company_id !== auth()->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ запрещен',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'new_end_date' => 'required|date|after:'.$order->end_date->format('Y-m-d'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $service = app(EquipmentAvailabilityService::class);
        $newEndDate = $request->new_end_date;

        foreach ($order->items as $item) {
            if (! $service->isAvailable(
                $item->equipment,
                $order->end_date->addDay()->format('Y-m-d'),
                $newEndDate
            )) {
                return response()->json([
                    'success' => false,
                    'message' => "Оборудование {$item->equipment->title} недоступно на выбранные даты",
                ], 400);
            }
        }

        $order->update([
            'extension_requested' => true,
            'requested_end_date' => $newEndDate,
            'status' => Order::STATUS_EXTENSION_REQUESTED,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Запрос на продление отправлен арендодателю',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'delivery_type' => 'required|in:pickup,delivery',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'items' => 'required|array|min:1',
            'items.*.equipment_id' => 'required|exists:equipment,id',
            'items.*.rental_term_id' => 'required|exists:equipment_rental_terms,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.period_count' => 'required|integer|min:1',
            'items.*.base_price' => 'required|numeric|min:0',
            'items.*.price_per_unit' => 'required|numeric|min:0',
            'items.*.platform_fee' => 'required|numeric|min:0',
            'items.*.delivery_cost' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $order = new Order;
            $order->lessee_company_id = auth()->user()->company_id;
            $order->status = Order::STATUS_PENDING;
            $order->delivery_type = $request->delivery_type;
            $order->start_date = $request->start_date;
            $order->end_date = $request->end_date;

            $order->fill($request->only([
                'notes',
                'delivery_address',
                'total_amount',
                'base_amount',
                'platform_fee',
                'delivery_cost',
                'discount_amount',
                'lessor_base_amount',
            ]));

            $order->save();

            foreach ($request->items as $itemData) {
                $orderItem = new OrderItem([
                    'equipment_id' => $itemData['equipment_id'],
                    'rental_term_id' => $itemData['rental_term_id'],
                    'quantity' => $itemData['quantity'],
                    'period_count' => $itemData['period_count'],
                    'base_price' => $itemData['base_price'],
                    'price_per_unit' => $itemData['price_per_unit'],
                    'platform_fee' => $itemData['platform_fee'],
                    'delivery_cost' => $itemData['delivery_cost'],
                    'total_price' => $itemData['total_price'],
                    'delivery_from_id' => $itemData['delivery_from_id'] ?? null,
                    'delivery_to_id' => $itemData['delivery_to_id'] ?? null,
                ]);

                $order->items()->save($orderItem);
            }
        });

        return redirect()->route('lessee.orders.show', $order)
            ->with('success', 'Заказ успешно создан');
    }
}
