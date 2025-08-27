<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\EquipmentAvailabilityService;
use App\Services\PricingService;
use App\Services\TransportCalculatorService;
use App\Services\DeliveryCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;
use App\Models\CartItem;
use App\Models\Company;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Platform;
use App\Models\EquipmentAvailability;

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
                'items.rentalTerm.equipment.specifications',
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
                // 1. Генерируем номер для родительского заказа арендатора
                $nextOrderNumber = $this->getNextCompanyOrderNumber(
                    auth()->user()->company_id, // lessee_company_id
                    null                        // lessor_company_id
                );

                // 2. Создаем главный заказ для арендатора
                $parentOrder = $this->createParentOrder($cartItems, $nextOrderNumber);

                // 3. Группируем и создаем дочерние заказы
                $groupedItems = $cartItems->groupBy(
                    fn($item) => $item->rentalTerm->equipment->company_id
                );

                foreach ($groupedItems as $companyId => $items) {
                    // Для каждого арендодателя генерируем свой порядковый номер
                    // Нумерация для арендодателя должна быть уникальной в рамках его компании
                    $lessorOrderNumber = $this->getNextCompanyOrderNumber(
                        null,       // lessee_company_id
                        $companyId  // lessor_company_id
                    );

                    $childOrder = $this->createChildOrder(
                        $parentOrder->id,
                        $companyId,
                        $items,
                        $lessorOrderNumber // Передаем номер для арендодателя
                    );

                    $parentOrder->childOrders()->save($childOrder);

                    // 4. Создаем позиции заказа и бронируем оборудование
                    foreach ($items as $item) {
                        $orderItem = $this->createOrderItem($childOrder, $item);
                        $this->bookEquipmentItem($item, $childOrder->id);
                    }
                }

                // 5. Очищаем корзину и фиксируем изменения
                $cart->items()->whereIn('id', $selectedItems)->delete();
                $this->cartService->recalculateTotals($cart);

                DB::commit();

                Log::info('[CHECKOUT] Заказ успешно оформлен', [
                    'parent_order_id' => $parentOrder->id,
                    'child_orders_count' => count($orders),
                    'delivery_cost_total' => $parentOrder->delivery_cost
                ]);

                return redirect()->route('lessee.orders.show', ['order' => $parentOrder->id])
                     ->with('success', 'Заказ #' . $parentOrder->company_order_number . ' успешно оформлен!');

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

  protected function createParentOrder($items, $orderNumber)
    {

        if ($items->isEmpty()) {
            throw new \Exception('Нельзя создать заказ без позиций');
        }

        // Рассчитываем суммы ИСКЛЮЧИТЕЛЬНО из фиксированных цен корзины
        $deliveryCost = $items->sum('delivery_cost');

        // Сумма, которую платит арендатор за аренду (уже включает наценку платформы)
        $customerRentalTotal = $items->sum(function($item) {
            return ($item->fixed_customer_price * $item->period_count);
        });

        // Заработок платформы (наценка)
         $platformFeeTotal = $items->sum('platform_fee');

        // Сумма, которая уйдет арендодателям (БЕЗ наценки платформы)
        $lessorBaseAmount = $items->sum(function($item) {
            return ($item->fixed_lessor_price * $item->period_count);
        });

        // Итоговая сумма к оплате арендатором
        $totalAmount = $customerRentalTotal + $deliveryCost;

        $deliveryType = $items->contains(function ($item) {
            return $item->delivery_to_id && $item->delivery_from_id;
        }) ? Order::DELIVERY_DELIVERY : Order::DELIVERY_PICKUP;

        return Order::create([
            'user_id' => auth()->id(),
            'lessee_company_id' => auth()->user()->company_id,
            'lessor_company_id' => null,
            'status' => Order::STATUS_AGGREGATED,
            'company_order_number' => $orderNumber, // Используем переданный номер
            'total_amount' => $totalAmount,
            'base_amount' => $customerRentalTotal, // Сумма аренды с наценкой
            'platform_fee' => $platformFeeTotal,   // Заработок платформы
            'lessor_base_amount' => $lessorBaseAmount, // Сумма арендодателям
            'delivery_cost' => $deliveryCost,
            'start_date' => $items->min('start_date'),
            'end_date' => $items->max('end_date'),
            'delivery_type' => $deliveryType,
        ]);
        if ($platformFeeTotal != $items->sum('platform_fee')) {
            \Log::warning('Возможна ошибка в расчете платформенной комиссии', [
                'calculated' => $platformFeeTotal,
                'expected' => $items->sum('platform_fee')
            ]);
        }
    }

   protected function createChildOrder($parentId, $companyId, $items, $orderNumber)
    {
        $rentalCondition = $items->first()->rentalCondition;

        // Расчеты для арендодателя: используем fixed_lessor_price!
        $deliveryCost = $items->sum('delivery_cost');
        $lessorBaseAmount = $items->sum(function($item) {
            return $item->fixed_lessor_price * $item->period_count;
        });

        // Итоговая сумма к выплате арендодателю (аренда + доставка)
        $totalPayout = $lessorBaseAmount + $deliveryCost;

        $deliveryType = $items->contains(function ($item) {
            return $item->delivery_to_id && $item->delivery_from_id;
        }) ? Order::DELIVERY_DELIVERY : Order::DELIVERY_PICKUP;

        return Order::create([
            'user_id' => auth()->id(),
            'parent_order_id' => $parentId,
            'lessor_company_id' => $companyId,
            'lessee_company_id' => auth()->user()->company_id,
            'status' => Order::STATUS_PENDING_APPROVAL,
            'company_order_number' => $orderNumber,
            'base_amount' => $totalPayout, // Для арендодателя это его итоговая выплата
            'lessor_base_amount' => $lessorBaseAmount, // Его часть за аренду
            'platform_fee' => 0, // Для арендодателя платформа не начисляет fee в его заказ
            'delivery_cost' => $deliveryCost,
            'total_amount' => $totalPayout,
            'start_date' => $items->min('start_date'),
            'end_date' => $items->max('end_date'),
            'platform_id' => Platform::getMain()->id,
            'contract_id' => $rentalCondition->contract_id ?? null,
            'rental_condition_id' => $rentalCondition->id,
            'shift_hours' => $rentalCondition->shift_hours,
            'shifts_per_day' => $rentalCondition->shifts_per_day,
            'payment_type' => $rentalCondition->payment_type,
            'transportation' => $rentalCondition->transportation,
            'fuel_responsibility' => $rentalCondition->fuel_responsibility,
            'delivery_location_id' => $items->first()->delivery_to_id,
            'delivery_from_id' => $items->first()->delivery_from_id,
            'delivery_to_id' => $items->first()->delivery_to_id,
            'delivery_type' => $deliveryType,
        ]);
    }

    protected function createOrderItem($childOrder, $cartItem)
    {
        $term = $cartItem->rentalTerm;
        $equipment = $term->equipment;

        // ВСЕ данные берем из корзины! Никаких новых расчетов.
        $customerRentalTotal = $cartItem->fixed_customer_price * $cartItem->period_count;
        $lessorRentalTotal = $cartItem->fixed_lessor_price * $cartItem->period_count;

        // Рассчитываем discount_amount
        $itemTotal = $customerRentalTotal + $cartItem->platform_fee;
        $orderTotal = $childOrder->base_amount + $childOrder->platform_fee;
        $discountAmount = $orderTotal > 0 ? ($itemTotal / $orderTotal) * $childOrder->discount_amount : 0;

        return OrderItem::create([
            'order_id' => $childOrder->id,
            'equipment_id' => $equipment->id,
            'rental_term_id' => $term->id,
            'rental_condition_id' => $cartItem->rental_condition_id,
            'quantity' => 1,
            'period_count' => $cartItem->period_count,
            // Цены для арендатора:
            'base_price' => $cartItem->fixed_customer_price,
            'price_per_unit' => $cartItem->fixed_customer_price,
            'fixed_customer_price' => $cartItem->fixed_customer_price,
            'platform_fee' => $cartItem->platform_fee,
            // Цены для арендодателя:
            'fixed_lessor_price' => $cartItem->fixed_lessor_price,
            // Доставка и итоги:
            'delivery_cost' => $cartItem->delivery_cost,
            'distance_km' => $cartItem->distance_km ?? 0,
            'total_price' => $customerRentalTotal + $cartItem->delivery_cost - $discountAmount, // Итог с учетом скидки
            'discount_amount' => $discountAmount, // Явно передаем скидку, даже если 0
            'delivery_from_id' => $cartItem->delivery_from_id,
            'delivery_to_id' => $cartItem->delivery_to_id,
            'lessor_company_id' => $childOrder->lessor_company_id,
            'status' => OrderItem::STATUS_PENDING,
        ]);
    }

    public function bookEquipmentItem($cartItem, $orderId)
    {
        // Проверяем наличие необходимых отношений
        if (!$cartItem->rentalTerm || !$cartItem->rentalTerm->equipment) {
            Log::error('Оборудование не найдено для элемента корзины', [
                'cart_item_id' => $cartItem->id,
                'rental_term_id' => $cartItem->rental_term_id
            ]);
            throw new \Exception("Оборудование для позиции #{$cartItem->id} не найдено");
        }

        $equipment = $cartItem->rentalTerm->equipment;
        $startDate = Carbon::parse($cartItem->start_date);
        $endDate = Carbon::parse($cartItem->end_date);

        // Проверяем доступность
        if (!$this->availabilityService->isAvailable($equipment, $startDate, $endDate)) {
            $nextAvailable = $this->availabilityService->calculateNextAvailableDate($equipment->id);
            $message = $nextAvailable
                ? "Оборудование {$equipment->title} недоступно. Ближайшая доступная дата: {$nextAvailable->format('d.m.Y')}"
                : "Оборудование {$equipment->title} недоступно на выбранные даты";
            throw new \Exception($message);
        }

        // Бронируем оборудование
        $this->availabilityService->bookEquipment(
            $equipment,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $orderId,
            'booked'
        );
    } // <-- Этой закрывающей скобки не хватало

    protected function getNextCompanyOrderNumber(?int $lesseeCompanyId, ?int $lessorCompanyId): int
    {
        // Определяем, для какой роли генерируем номер
        if (!is_null($lesseeCompanyId)) {
            // Если это заказ арендатора (родительский) - ищем макс. номер по lessee_company_id
            $lastOrder = Order::where('lessee_company_id', $lesseeCompanyId)
                            ->orderBy('company_order_number', 'desc')
                            ->first();
        } elseif (!is_null($lessorCompanyId)) {
            // Если это заказ арендодателя (дочерний) - ищем макс. номер по lessor_company_id
            $lastOrder = Order::where('lessor_company_id', $lessorCompanyId)
                            ->orderBy('company_order_number', 'desc')
                            ->first();
        } else {
            // Если оба null (маловероятно в вашей схеме), возвращаем 1
            return 1;
        }

        return ($lastOrder->company_order_number ?? 0) + 1;
    }
}

