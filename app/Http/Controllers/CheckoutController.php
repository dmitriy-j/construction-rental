<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\EquipmentAvailabilityService;
use App\Services\PricingService;
use App\Services\TransportCalculatorService;
use App\Services\DeliveryCalculatorService;
use App\Models\Order;
use App\Models\Platform;
use App\Models\DeliveryNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

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
                    $childOrder = $this->createChildOrder(
                        $parentOrder->id, // parent_order_id
                        $companyId,
                        $items
                    );

                    // Добавляем явную связь
                    $parentOrder->childOrders()->save($childOrder);
                    $orders[] = $childOrder;

                    Log::info('[CHECKOUT] Дочерний заказ создан', [
                        'order_id' => $childOrder->id,
                        'company_id' => $companyId,
                        'item_count' => $items->count()
                    ]);

                    foreach ($items as $item) {
                        // 4. Создаем позиции в дочернем заказе
                        $this->createOrderItem($childOrder, $item);
                        $this->bookEquipment($item, $childOrder->id);

                        if ($item->deliveryNote) {
                            $item->deliveryNote->update(['cart_item_id' => null]);
                        }
                    }
                }

                // 5. Удаляем элементы из корзины
                Log::info('[CHECKOUT] Удаление элементов из корзины', [
                    'item_ids' => $selectedItems
                ]);
                $cart->items()->whereIn('id', $selectedItems)->delete();
                $this->cartService->recalculateTotals($cart);

                DB::commit();

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
        $totalAmount = $items->sum(function($item) {
            return ($item->base_price * $item->period_count) + $item->platform_fee + $item->delivery_cost;
        });

        return Order::create([
            'user_id' => auth()->id(),
            'lessee_company_id' => auth()->user()->company_id,
            'lessor_company_id' => null, // Явное указание NULL
            'status' => Order::STATUS_AGGREGATED,
            'total_amount' => $totalAmount,
            'start_date' => $items->min('start_date'),
            'end_date' => $items->max('end_date'),
        ]);
    }

    protected function createChildOrder($parentId, $companyId, $items)
    {
        $baseAmount = $items->sum(function($item) {
            return $item->base_price * $item->period_count;
        });

        $platformFee = $items->sum('platform_fee');
        $deliveryCost = $items->sum('delivery_cost');
        $totalAmount = $baseAmount + $platformFee + $deliveryCost;

        $discountAmount = $this->pricingService->getDiscount(
            auth()->user()->company,
            $baseAmount + $platformFee
        );

        $totalAmount -= $discountAmount;

        return Order::create([
            'user_id' => auth()->id(), // Добавляем user_id
            'parent_order_id' => $parentId,
            'lessor_company_id' => $companyId,
            'lessee_company_id' => auth()->user()->company_id,
            'status' => Order::STATUS_PENDING_APPROVAL,
            'base_amount' => $baseAmount,
            'platform_fee' => $platformFee,
            'delivery_cost' => $deliveryCost,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'start_date' => $items->min('start_date'),
            'end_date' => $items->max('end_date'),
            'platform_id' => Platform::getMain()->id,
            'rental_condition_id' => $items->first()->rental_condition_id
        ]);
    }

    protected function createOrderItem($order, $item)
    {
        $term = $item->rentalTerm;
        $equipment = $term->equipment;

        // Рассчитываем скидку для позиции
        $itemTotal = ($item->base_price * $item->period_count) + $item->platform_fee;
        $orderTotal = $order->base_amount + $order->platform_fee;

        $discountAmount = 0;
        if ($orderTotal > 0 && $order->discount_amount > 0) {
            $discountAmount = round(($itemTotal / $orderTotal) * $order->discount_amount, 2);
        }

        $orderItem = \App\Models\OrderItem::create([
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
            'delivery_cost' => $item->delivery_cost
        ]);

        if ($item->delivery_cost > 0) {
            $deliveryDays = $term->delivery_days ?? 1;
            $deliveryDate = Carbon::parse($item->start_date)->subDays($deliveryDays);

            $vehicleType = $this->calculateVehicleType($item);
            $distance = $this->calculateDistance($item);

            DeliveryNote::create([
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'delivery_from_id' => $item->delivery_from_id,
                'delivery_to_id' => $item->delivery_to_id,
                'vehicle_type' => $vehicleType,
                'distance_km' => $distance,
                'calculated_cost' => $item->delivery_cost,
                'delivery_date' => $deliveryDate,
                'driver_name' => 'Не указано',
                'receiver_name' => 'Не указано',
                'equipment_condition' => 'Хорошее'
            ]);
        }

        return $orderItem;
    }

    protected function calculateItemDiscount($item, $orderDiscount)
    {
        // Если скидка нулевая - возвращаем 0
        if ($orderDiscount <= 0) {
            return 0;
        }

        // Рассчитываем общую стоимость позиции
        $itemTotal = ($item->base_price * $item->period_count) + $item->platform_fee;

        // Рассчитываем долю позиции в общей сумме заказа
        $orderTotal = $order->base_amount + $order->platform_fee;

        if ($orderTotal <= 0) {
            return 0;
        }

        // Скидка пропорциональна вкладу позиции в общую сумму
        return round(($itemTotal / $orderTotal) * $orderDiscount, 2);
    }

    protected function bookEquipment($item, $orderId)
    {
        $equipment = $item->rentalTerm->equipment;
        $rentalTerm = $item->rentalTerm;
        $deliveryDays = $rentalTerm->delivery_days ?? 0;

        $startDate = Carbon::parse($item->start_date);
        $endDate = Carbon::parse($item->end_date);

        Log::debug('[BOOKING] Параметры бронирования', [
            'equipment_id' => $equipment->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'delivery_days' => $deliveryDays,
            'now' => now()->format('Y-m-d H:i:s'),
            'item_id' => $item->id
        ]);

        $isAvailable = $this->availabilityService->isAvailable(
            $equipment,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        Log::debug('[BOOKING] Результат проверки доступности', [
            'available' => $isAvailable
        ]);

        if (!$isAvailable) {
            $nextAvailable = $this->availabilityService->calculateNextAvailableDate($equipment->id);
            Log::warning('[BOOKING] Оборудование недоступно', [
                'next_available' => $nextAvailable ? $nextAvailable->format('Y-m-d') : null
            ]);
            throw new \Exception("Оборудование {$equipment->title} недоступно. " .
                ($nextAvailable ? "Ближайшая доступная дата: {$nextAvailable->format('d.m.Y')}" : ""));
        }

        $this->availabilityService->bookEquipment(
            $equipment,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $orderId,
            'booked'
        );

        Log::info('[BOOKING] Оборудование успешно забронировано');
    }

    protected function calculateVehicleType($item): string
    {
        try {
            return app(TransportCalculatorService::class)
                ->calculateRequiredTransport($item->rentalTerm->equipment);
        } catch (\Exception $e) {
            Log::error('Ошибка расчета типа транспорта', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            return 'truck_25t';
        }
    }

    protected function calculateDistance($item): float
    {
        try {
            return app(DeliveryCalculatorService::class)->calculateDistance(
                $item->deliveryFrom->latitude,
                $item->deliveryFrom->longitude,
                $item->deliveryTo->latitude,
                $item->deliveryTo->longitude
            );
        } catch (\Exception $e) {
            Log::error('Ошибка расчета расстояния', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            return 50.0;
        }
    }
}
