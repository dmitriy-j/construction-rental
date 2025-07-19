<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\EquipmentAvailabilityService;
use App\Services\PricingService;
use App\Services\TransportCalculatorService;
use App\Services\DeliveryCalculatorService;
use App\Models\Order;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;
use App\Models\CartItem;

class CheckoutController extends Controller
{
    protected $cartService;
    protected $availabilityService;
    protected $pricingService;

    public function __construct(
        CartService $cartService,
        EquipmentAvailabilityService $availabilityService,
        PricingService $pricingService
    ) {
        $this->cartService = $cartService;
        $this->availabilityService = $availabilityService;
        $this->pricingService = $pricingService;
    }

    public function checkout(Request $request)
    {
        Log::info('[CHECKOUT] Начало оформления заказа', [
            'user_id' => auth()->id(),
            'selected_items' => $request->input('selected_items', ''),
            'all_request_data' => $request->all()
        ]);

        try {
            $cart = $this->cartService->getCart();
            $selectedItems = $request->input('selected_items', '');
            $selectedItems = json_decode($selectedItems, true) ?? [];

            if (empty($selectedItems)) {
                Log::warning('[CHECKOUT] Параметр selected_items пуст, используем всю корзину');
                $selectedItems = $cart->items->pluck('id')->toArray();
            }

            Log::debug('[CHECKOUT] Получена корзина', [
                'cart_id' => $cart->id,
                'item_count' => $cart->items->count(),
                'selected_items_count' => count($selectedItems)
            ]);

            $cart->load([
                'items.rentalTerm.equipment.company',
                'items.rentalCondition',
                'items.deliveryFrom',
                'items.deliveryTo'
            ]);

            $cartItems = $cart->items->filter(fn($item) => in_array($item->id, $selectedItems));

            // Добавляем детальное логирование
            Log::debug('[CHECKOUT] Cart items details', [
                'items' => $cartItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'delivery_cost' => $item->delivery_cost,
                        'delivery_from' => $item->delivery_from_id,
                        'delivery_to' => $item->delivery_to_id
                    ];
                })
            ]);

            if ($cartItems->isEmpty()) {
                Log::warning('[CHECKOUT] Корзина пуста после фильтрации');
                return redirect()->back()->with('error', 'Корзина пуста');
            }

            DB::beginTransaction();

            try {
                // 1. Создаем главный заказ для арендатора
                $parentOrder = $this->createParentOrder($cartItems);

                // 2. Группируем позиции по арендодателям
                $groupedItems = $cartItems->groupBy(
                    fn($item) => $item->rentalTerm->equipment->company_id
                );

                $orders = [];

                foreach ($groupedItems as $companyId => $items) {

                    // ДОБАВЛЕНО: Логирование перед созданием дочернего заказа
                Log::debug('[CHECKOUT] Creating child order', [
                    'company_id' => $companyId,
                    'delivery_cost_total' => $items->sum('delivery_cost')
                ]);

                    $childOrder = $this->createChildOrder(
                        $parentOrder->id,
                        $companyId,
                        $items
                    );

                    $parentOrder->childOrders()->save($childOrder);
                    $orders[] = $childOrder;

                    Log::info('[CHECKOUT] Дочерний заказ создан', [
                        'order_id' => $childOrder->id,
                        'company_id' => $companyId,
                        'item_count' => $items->count()
                    ]);

                    foreach ($items as $item) {
                        // Создаем позиции и бронируем оборудование
                        $this->createOrderItem($childOrder, $item);
                        $this->bookEquipment($item, $childOrder->id);
                    }
                }

                // Удаляем элементы из корзины
                $cart->items()->whereIn('id', $selectedItems)->delete();
                $this->cartService->recalculateTotals($cart);

                DB::commit();

                Log::info('[CHECKOUT] Заказ успешно оформлен', [
                    'parent_order_id' => $parentOrder->id,
                    'child_orders_count' => count($orders),
                    'delivery_cost_total' => $parentOrder->delivery_cost
                ]);

                return redirect()->route('lessee.orders.show', $parentOrder)
                    ->with('success', 'Заказы успешно оформлены! Создано подзаказов: ' . count($orders));

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('[CHECKOUT] Ошибка в транзакции', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', 'Ошибка оформления заказа: ' . $e->getMessage());
            }

        } catch (Exception $e) {
            Log::error('[CHECKOUT] Критическая ошибка', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Произошла непредвиденная ошибка: ' . $e->getMessage());
        }
    }

   protected function createParentOrder($items)
    {
        // Исправленный расчет!
        $deliveryCost = $items->sum('delivery_cost');

        $lessorBaseAmount = $items->sum(function($item) {
            return $item->rentalTerm->price_per_hour * $item->period_count;
        });

        $platformFee = $items->sum('platform_fee');
        $baseAmount = $lessorBaseAmount + $platformFee;
        $totalAmount = $baseAmount + $deliveryCost;

        // Логирование перед созданием
        Log::debug('[CHECKOUT] Creating parent order', [
            'delivery_cost' => $deliveryCost,
            'items_count' => $items->count()
        ]);

        return Order::create([
            'user_id' => auth()->id(),
            'lessee_company_id' => auth()->user()->company_id,
            'lessor_company_id' => null,
            'status' => Order::STATUS_AGGREGATED,
            'total_amount' => $totalAmount,
            'base_amount' => $baseAmount,
            'lessor_base_amount' => $lessorBaseAmount,
            'platform_fee' => $platformFee,
            'delivery_cost' => $deliveryCost,
            'start_date' => $items->min('start_date'),
            'end_date' => $items->max('end_date'),
        ]);
    }

   protected function createChildOrder($parentId, $companyId, $items)
    {
        // Правильный расчет сумм
        $deliveryCost = $items->sum('delivery_cost');
        $lessorBaseAmount = $items->sum(function($item) {
            return $item->base_price * $item->period_count;
        });

        $platformFee = $items->sum('platform_fee');
        $baseAmount = $lessorBaseAmount + $platformFee;
        $totalAmount = $baseAmount + $deliveryCost;

        $discountAmount = $this->pricingService->getDiscount(
            auth()->user()->company,
            $baseAmount
        );

        $totalAmount -= $discountAmount;

        $rentalCondition = $items->first()->rentalCondition;

        Log::debug('[CHECKOUT] Creating child order', [
            'company_id' => $companyId,
            'delivery_cost' => $deliveryCost,
            'lessor_base_amount' => $lessorBaseAmount,
            'platform_fee' => $platformFee,
            'items_count' => $items->count()
        ]);

        return Order::create([
            'user_id' => auth()->id(),
            'parent_order_id' => $parentId,
            'lessor_company_id' => $companyId,
            'lessee_company_id' => auth()->user()->company_id,
            'status' => Order::STATUS_PENDING_APPROVAL,
            'base_amount' => $baseAmount,
            'lessor_base_amount' => $lessorBaseAmount,
            'platform_fee' => $platformFee,
            'delivery_cost' => $deliveryCost,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'start_date' => $items->min('start_date'),
            'end_date' => $items->max('end_date'),
            'platform_id' => Platform::getMain()->id,
            'rental_condition_id' => $rentalCondition->id,
            'shift_hours' => $rentalCondition->shift_hours,
            'shifts_per_day' => $rentalCondition->shifts_per_day,
            'payment_type' => $rentalCondition->payment_type,
            'transportation' => $rentalCondition->transportation,
            'fuel_responsibility' => $rentalCondition->fuel_responsibility,
            'delivery_location_id' => $items->first()->delivery_to_id,
            'delivery_from_id' => $items->first()->delivery_from_id, // Исправлено: добавлена запятая
            'delivery_to_id' => $items->first()->delivery_to_id,     // Исправлено: добавлена запятая
        ]);
    }

    protected function createOrderItem($order, $item)
    {
        $term = $item->rentalTerm;
        $equipment = $term->equipment;

        $itemTotal = ($item->base_price * $item->period_count) + $item->platform_fee;
        $orderTotal = $order->base_amount + $order->platform_fee;

        $discountAmount = 0;
        if ($orderTotal > 0 && $order->discount_amount > 0) {
            $discountAmount = round(($itemTotal / $orderTotal) * $order->discount_amount, 2);
        }

        $deliveryCost = (float)$item->delivery_cost;

        // Детальное логирование перед созданием
        Log::debug('[CHECKOUT] Creating order item', [
            'equipment_id' => $equipment->id,
            'delivery_cost' => $deliveryCost,
            'delivery_from_id' => $item->delivery_from_id,
            'delivery_to_id' => $item->delivery_to_id,
            'item_data' => $item->toArray() // Логируем все данные элемента
        ]);

        $orderItemData = [
            'order_id' => $order->id,
            'equipment_id' => $equipment->id,
            'rental_term_id' => $term->id,
            'rental_condition_id' => $item->rental_condition_id,
            'period_count' => $item->period_count,
            'quantity' => 1,
            'base_price' => $item->base_price,
            'price_per_unit' => $item->base_price,
            'platform_fee' => $item->platform_fee,
            'discount_amount' => $discountAmount,
            'total_price' => $itemTotal - $discountAmount,
            'delivery_cost' => $deliveryCost,
            'delivery_from_id' => $item->delivery_from_id,
            'delivery_to_id' => $item->delivery_to_id,
        ];

        // Создаем позицию заказа
        $orderItem = \App\Models\OrderItem::create($orderItemData);

        // Логирование после создания
        Log::debug('[CHECKOUT] Order item created', [
            'id' => $orderItem->id,
            'delivery_from_id' => $orderItem->delivery_from_id,
            'delivery_to_id' => $orderItem->delivery_to_id,
            'saved_data' => $orderItem->toArray()
        ]);

        return $orderItem;
    }
    protected function bookEquipment($item, $orderId)
    {
        $equipment = $item->rentalTerm->equipment;
        $startDate = Carbon::parse($item->start_date);
        $endDate = Carbon::parse($item->end_date);

        if (!$this->availabilityService->isAvailable(
            $equipment,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        )) {
            $nextAvailable = $this->availabilityService->calculateNextAvailableDate($equipment->id);
            $message = $nextAvailable
                ? "Оборудование {$equipment->title} недоступно. Ближайшая доступная дата: {$nextAvailable->format('d.m.Y')}"
                : "Оборудование {$equipment->title} недоступно на выбранные даты";

            throw new \Exception($message);
        }

        $this->availabilityService->bookEquipment(
            $equipment,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $orderId,
            'booked'
        );
    }
}
