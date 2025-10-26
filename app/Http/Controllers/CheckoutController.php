<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Platform;
use App\Models\Equipment;
use App\Services\CartService;
use App\Services\EquipmentAvailabilityService;
use App\Services\PricingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'all_request_data' => $request->all(),
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
                'selected_items_count' => count($selectedItems),
            ]);

            $cart->load([
                'items.rentalTerm.equipment.specifications',
                'items.rentalTerm.equipment.company',
                'items.rentalCondition',
                'items.deliveryFrom',
                'items.deliveryTo',
            ]);

            $cartItems = $cart->items->filter(fn ($item) => in_array($item->id, $selectedItems));

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
                $parentOrder = $this->createRegularParentOrder($cartItems, $nextOrderNumber);

                // 3. Группируем и создаем дочерние заказы
                $groupedItems = $cartItems->groupBy(
                    fn ($item) => $item->rentalTerm->equipment->company_id
                );

                foreach ($groupedItems as $companyId => $items) {
                    // Для каждого арендодателя генерируем свой порядковый номер
                    $lessorOrderNumber = $this->getNextCompanyOrderNumber(
                        null,       // lessee_company_id
                        $companyId  // lessor_company_id
                    );

                    // 🔥 ИСПРАВЛЕНО: Используем правильный метод для обычных заказов
                    $childOrder = $this->createRegularChildOrder(
                        $parentOrder->id,
                        $items,   // Передаем коллекцию объектов
                        $companyId,
                        $lessorOrderNumber
                    );

                    $parentOrder->childOrders()->save($childOrder);

                    // 🔥 УБРАНО ДУБЛИРОВАНИЕ: Удаляем вызов createOrderItem, т.к. он уже выполняется внутри createRegularChildOrder
                    // 4. Только бронируем оборудование (создание элементов заказа уже выполнено в createRegularChildOrder)
                    foreach ($items as $item) {
                        $this->bookEquipmentItem($item, $childOrder->id);
                    }
                }

                // 5. Очищаем корзину и фиксируем изменения
                $cart->items()->whereIn('id', $selectedItems)->delete();
                $this->cartService->recalculateTotals($cart);

                DB::commit();

                Log::info('[CHECKOUT] Заказ успешно оформлен', [
                    'parent_order_id' => $parentOrder->id,
                    'child_orders_count' => count($groupedItems),
                    'delivery_cost_total' => $parentOrder->delivery_cost,
                ]);

                return redirect()->route('lessee.orders.show', ['order' => $parentOrder->id])
                    ->with('success', 'Заказ #'.$parentOrder->company_order_number.' успешно оформлен!');

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('[CHECKOUT] Ошибка в транзакции', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->back()->with('error', 'Ошибка оформления заказа: '.$e->getMessage());
            }

        } catch (Exception $e) {
            Log::error('[CHECKOUT] Критическая ошибка', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Произошла непредвиденная ошибка: '.$e->getMessage());
        }
    }

    /**
     * 🔥 ИСПРАВЛЕННЫЙ МЕТОД: Создание родительского заказа для обычных заказов
     */
    protected function createRegularParentOrder($cartItems, $orderNumber)
    {
        if ($cartItems->isEmpty()) {
            throw new \Exception('Нельзя создать заказ без позиций');
        }

        // Рассчитываем суммы
        $deliveryCost = $cartItems->sum('delivery_cost');
        $customerRentalTotal = $cartItems->sum(function ($item) {
            return $item->base_price * $item->period_count;
        });

        $platformFeeTotal = $cartItems->sum('platform_fee');
        $lessorBaseAmount = $cartItems->sum(function ($item) {
            return $item->fixed_lessor_price * $item->period_count;
        });

        $totalAmount = $customerRentalTotal + $deliveryCost;

        $deliveryType = $cartItems->contains(function ($item) {
            return $item->delivery_to_id && $item->delivery_from_id;
        }) ? Order::DELIVERY_DELIVERY : Order::DELIVERY_PICKUP;

        return Order::create([
            'user_id' => auth()->id(),
            'lessee_company_id' => auth()->user()->company_id,
            'lessor_company_id' => null,
            'status' => Order::STATUS_AGGREGATED,
            'company_order_number' => $orderNumber,
            'total_amount' => $totalAmount,
            'base_amount' => $customerRentalTotal,
            'platform_fee' => $platformFeeTotal,
            'lessor_base_amount' => $lessorBaseAmount,
            'delivery_cost' => $deliveryCost,
            'start_date' => $cartItems->min('start_date'),
            'end_date' => $cartItems->max('end_date'),
            'delivery_type' => $deliveryType,
            'type' => 'regular', // 🔥 Отмечаем, что заказ создан из обычной корзины
        ]);
    }

    /**
     * 🔥 НОВЫЙ МЕТОД: Создание дочернего заказа для обычных заказов
     */
    private function createRegularChildOrder(int $parentOrderId, $items, int $lessorCompanyId, int $orderNumber): Order
    {
        $totalAmount = 0;
        $orderItems = [];

        foreach ($items as $cartItem) {
            // 🔥 Работаем с объектами
            $rentalTotal = $cartItem->base_price * $cartItem->period_count;
            $itemTotal = $rentalTotal + ($cartItem->delivery_cost ?? 0);
            $totalAmount += $itemTotal;

            $orderItems[] = [
                'equipment_id' => $cartItem->equipment_id ?? $cartItem->rentalTerm->equipment_id,
                'rental_term_id' => $cartItem->rental_term_id,
                'rental_condition_id' => $cartItem->rental_condition_id,
                'quantity' => 1,
                'base_price' => $cartItem->base_price,
                'price_per_unit' => $cartItem->base_price,
                'platform_fee' => $cartItem->platform_fee,
                'period_count' => $cartItem->period_count,
                'delivery_cost' => $cartItem->delivery_cost ?? 0,
                'total_price' => $itemTotal,
                'fixed_lessor_price' => $cartItem->fixed_lessor_price,
                'fixed_customer_price' => $cartItem->fixed_customer_price,
                'proposal_id' => null,
                'start_date' => $cartItem->start_date,
                'end_date' => $cartItem->end_date,
                'discount_amount' => 0,
                'distance_km' => $cartItem->distance_km ?? 0,
                'status' => OrderItem::STATUS_PENDING, // 🔥 Добавляем статус
            ];
        }

        $childOrder = Order::create([
            'parent_order_id' => $parentOrderId,
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id,
            'lessee_company_id' => auth()->user()->company_id,
            'lessor_company_id' => $lessorCompanyId,
            'status' => Order::STATUS_PENDING_APPROVAL,
            'total_amount' => $totalAmount,
            'start_date' => $items->min('start_date'),
            'end_date' => $items->max('end_date'),
            'company_order_number' => $orderNumber,
            'discount_amount' => 0,
        ]);

        // Создаем элементы заказа
        foreach ($orderItems as $itemData) {
            $childOrder->items()->create($itemData);
        }

        \Log::info('[CHECKOUT] Created regular child order', [
            'child_order_id' => $childOrder->id,
            'parent_order_id' => $parentOrderId,
            'lessee_company_id' => auth()->user()->company_id,
            'lessor_company_id' => $lessorCompanyId,
            'items_count' => count($orderItems),
            'total_amount' => $totalAmount
        ]);

        return $childOrder;
    }

    /**
     * 🔥 ИСПРАВЛЕННЫЙ МЕТОД: Оформление заказа из корзины предложений
     */
    public function processProposalCheckout(Request $request)
    {
        \Log::info('[CHECKOUT] Starting proposal checkout process', [
            'user_id' => auth()->id(),
            'selected_items' => $request->selected_items
        ]);

        DB::beginTransaction();

        try {
            $selectedItems = json_decode($request->selected_items, true);

            if (empty($selectedItems)) {
                throw new \Exception('Не выбраны элементы для оформления');
            }

            $cart = Cart::where('user_id', auth()->id())
                        ->where('type', Cart::TYPE_PROPOSAL)
                        ->first();

            if (!$cart) {
                throw new \Exception('Корзина предложений не найдена');
            }

            // Загружаем выбранные элементы с отношениями
            $cartItems = CartItem::with([
                'proposal.equipment',
                'proposal.lessor.company',
                'rentalCondition',
                'proposal.rentalRequest'
            ])->whereIn('id', $selectedItems)
            ->where('cart_id', $cart->id)
            ->get();

            if ($cartItems->isEmpty()) {
                throw new \Exception('Выбранные элементы не найдены в корзине');
            }

            $availabilityService = app(EquipmentAvailabilityService::class);

            // 🔥 УЛУЧШЕННАЯ ПРОВЕРКА ДОСТУПНОСТИ
            foreach ($cartItems as $cartItem) {
                $isAvailable = $availabilityService->isAvailableForCart(
                    $cartItem->proposal->equipment,
                    $cartItem->start_date,
                    $cartItem->end_date,
                    $cart->reservation_token
                );

                if (!$isAvailable) {
                    $nextAvailable = $availabilityService->calculateNextAvailableDate($cartItem->proposal->equipment->id);
                    $errorMsg = $nextAvailable
                        ? "Оборудование '{$cartItem->proposal->equipment->title}' недоступно на выбранные даты. Ближайшая доступная дата: " . $nextAvailable->format('d.m.Y')
                        : "Оборудование '{$cartItem->proposal->equipment->title}' недоступно на выбранные даты.";

                    throw new \Exception($errorMsg);
                }
            }

            // 🔥 СОЗДАЕМ РОДИТЕЛЬСКИЙ ЗАКАЗ ДЛЯ ПРЕДЛОЖЕНИЙ
            $parentOrderNumber = $this->getNextCompanyOrderNumber(auth()->user()->company_id, null);
            $parentOrder = $this->createProposalParentOrder($cartItems, $parentOrderNumber);

            // Создаем дочерние заказы по арендодателям
            $ordersByLessor = $this->groupItemsByLessor($cartItems);
            $childOrdersCount = count($ordersByLessor);

            foreach ($ordersByLessor as $lessorCompanyId => $items) {
                // 🔥 ИСПРАВЛЕНО: Используем правильный метод для предложений
                $lessorOrderNumber = $this->getNextCompanyOrderNumber(null, $lessorCompanyId);
                $childOrder = $this->createProposalChildOrder(
                    $parentOrder->id,
                    $lessorCompanyId,
                    $items,
                    $lessorOrderNumber
                );

                $parentOrder->childOrders()->save($childOrder);

                // Преобразуем временные резервы в постоянные
                foreach ($items as $cartItem) {
                    $this->convertTempReservationToBooked(
                        $cartItem->proposal->equipment,
                        $cartItem->start_date,
                        $cartItem->end_date,
                        $childOrder->id,
                        $cart->reservation_token
                    );

                    // 🔥 СОЗДАЕМ ПОЗИЦИИ ЗАКАЗА ДЛЯ ПРЕДЛОЖЕНИЙ
                    $this->createProposalOrderItem($childOrder, $cartItem);
                }
            }

            // 🔥 УДАЛЯЕМ ОФОРМЛЕННЫЕ ЭЛЕМЕНТЫ ИЗ КОРЗИНЫ
            CartItem::whereIn('id', $selectedItems)->delete();

            // 🔥 ОБНОВЛЯЕМ СУММЫ КОРЗИНЫ
            app(\App\Services\CartService::class)->recalculateTotals($cart);

            DB::commit();

            \Log::info('[CHECKOUT] Proposal checkout completed successfully', [
                'parent_order_id' => $parentOrder->id,
                'child_orders_count' => $childOrdersCount,
                'user_id' => auth()->id()
            ]);

            // 🔥 ВОЗВРАЩАЕМ УСПЕШНЫЙ ОТВЕТ С redirect_url
            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно оформлен!',
                'data' => [
                    'order_id' => $parentOrder->id,
                    'order_number' => $parentOrder->company_order_number,
                    'redirect_url' => route('lessee.orders.show', $parentOrder->id)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('[CHECKOUT] Proposal checkout failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'selected_items' => $request->selected_items
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка оформления заказа: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * 🔥 ГРУППИРОВКА ПО АРЕНДОДАТЕЛЯМ
     */
    private function groupItemsByLessor($cartItems): array
    {
        $grouped = [];

        foreach ($cartItems as $cartItem) {
            // 🔥 ДОБАВЛЯЕМ ПРОВЕРКУ НАЛИЧИЯ ОТНОШЕНИЙ
            if (!$cartItem->proposal || !$cartItem->proposal->lessor) {
                \Log::error('[CHECKOUT] Missing proposal or lessor relationship', [
                    'cart_item_id' => $cartItem->id,
                    'proposal_id' => $cartItem->proposal_id
                ]);
                continue;
            }

            $lessorCompanyId = $cartItem->proposal->lessor->company_id;
            if (!isset($grouped[$lessorCompanyId])) {
                $grouped[$lessorCompanyId] = [];
            }
            $grouped[$lessorCompanyId][] = $cartItem;
        }

        \Log::info('[CHECKOUT] Grouped items by lessor', [
            'lessors_count' => count($grouped),
            'lessor_ids' => array_keys($grouped)
        ]);

        return $grouped;
    }

    /**
     * 🔥 ПРЕОБРАЗОВАНИЕ ВРЕМЕННОГО РЕЗЕРВИРОВАНИЯ В ПОСТОЯННОЕ
     */
    private function convertTempReservationToBooked(
        \App\Models\Equipment $equipment,
        $startDate,
        $endDate,
        int $orderId,
        ?string $reservationToken = null
    ): void {
        // Освобождаем временное резервирование
        $this->availabilityService->releaseEquipmentReservation($equipment, $startDate, $endDate, $reservationToken);

        // Создаем постоянное бронирование
        $this->availabilityService->bookEquipment(
            $equipment,
            $startDate,
            $endDate,
            $orderId,
            'booked'
        );

        \Log::info('[CHECKOUT] Converted temp reservation to booked', [
            'equipment_id' => $equipment->id,
            'order_id' => $orderId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    /**
     * 🔥 ИСПРАВЛЕННЫЙ МЕТОД: Создание родительского заказа для предложений
     */
    protected function createProposalParentOrder($items, $orderNumber)
    {
        if ($items->isEmpty()) {
            throw new \Exception('Нельзя создать заказ без позиций');
        }

        // Для предложений используем фиксированные цены из корзины
        $deliveryCost = $items->sum('delivery_cost');
        $customerRentalTotal = $items->sum(function ($item) {
            return $item->base_price * $item->period_count;
        });

        // Для предложений platform_fee обычно 0 (скрыт от пользователя)
        $platformFeeTotal = $items->sum('platform_fee');

        // Сумма для арендодателей
        $lessorBaseAmount = $items->sum(function ($item) {
            return $item->fixed_lessor_price * $item->period_count;
        });

        $totalAmount = $customerRentalTotal + $deliveryCost;

        $deliveryType = $items->contains(function ($item) {
            return $item->delivery_to_id && $item->delivery_from_id;
        }) ? Order::DELIVERY_DELIVERY : Order::DELIVERY_PICKUP;

        return Order::create([
            'user_id' => auth()->id(),
            'lessee_company_id' => auth()->user()->company_id,
            'lessor_company_id' => null,
            'status' => Order::STATUS_AGGREGATED,
            'company_order_number' => $orderNumber,
            'total_amount' => $totalAmount,
            'base_amount' => $customerRentalTotal,
            'platform_fee' => $platformFeeTotal,
            'lessor_base_amount' => $lessorBaseAmount,
            'delivery_cost' => $deliveryCost,
            'start_date' => $items->min('start_date'),
            'end_date' => $items->max('end_date'),
            'delivery_type' => $deliveryType,
            'type' => 'proposal', // Отмечаем, что заказ создан из предложений
            'rental_request_id' => $items->first()->cart->rental_request_id,
        ]);
    }

    /**
     * Создание дочернего заказа для предложений
     */
    protected function createProposalChildOrder($parentId, $companyId, $items, $orderNumber)
    {
        \Log::debug('[PROPOSAL_CHECKOUT_DEBUG] Creating child order', [
            'parent_id' => $parentId,
            'company_id' => $companyId,
            'items_count' => count($items),
            'order_number' => $orderNumber,
        ]);

        $firstItem = $items[0];

        // 🔥 КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Проверяем rentalCondition
        $rentalCondition = $firstItem->rentalCondition;

        \Log::debug('[PROPOSAL_CHECKOUT_DEBUG] Rental condition check', [
            'rental_condition_id' => $firstItem->rental_condition_id,
            'rental_condition_exists' => !is_null($rentalCondition),
            'item_id' => $firstItem->id
        ]);

        // 🔥 ЕСЛИ rentalCondition ОТСУТСТВУЕТ - создаем временный объект с дефолтными значениями
        if (!$rentalCondition) {
            \Log::warning('[PROPOSAL_CHECKOUT] Rental condition not found, using defaults', [
                'item_id' => $firstItem->id,
                'rental_condition_id' => $firstItem->rental_condition_id
            ]);

            // Создаем временный объект с дефолтными значениями
            $rentalCondition = new \stdClass();
            $rentalCondition->id = null;
            $rentalCondition->shift_hours = 8;
            $rentalCondition->shifts_per_day = 1;
            $rentalCondition->payment_type = 'hourly';
            $rentalCondition->transportation = 'lessor';
            $rentalCondition->fuel_responsibility = 'lessor';
            $rentalCondition->delivery_location_id = $firstItem->delivery_to_id;
            $rentalCondition->contract_id = null;
        }

        // Для предложений используем цены из предложения
        $deliveryCost = collect($items)->sum('delivery_cost');
        $lessorBaseAmount = collect($items)->sum(function ($item) {
            return $item->fixed_lessor_price * $item->period_count;
        });

        $totalPayout = $lessorBaseAmount + $deliveryCost;

        $deliveryType = collect($items)->contains(function ($item) {
            return $item->delivery_to_id && $item->delivery_from_id;
        }) ? Order::DELIVERY_DELIVERY : Order::DELIVERY_PICKUP;

        \Log::debug('[PROPOSAL_CHECKOUT_DEBUG] Child order calculations', [
            'delivery_cost' => $deliveryCost,
            'lessor_base_amount' => $lessorBaseAmount,
            'total_payout' => $totalPayout,
            'delivery_type' => $deliveryType
        ]);

        return Order::create([
            'user_id' => auth()->id(),
            'parent_order_id' => $parentId,
            'lessor_company_id' => $companyId,
            'lessee_company_id' => auth()->user()->company_id,
            'status' => Order::STATUS_PENDING_APPROVAL,
            'company_order_number' => $orderNumber,
            'base_amount' => $totalPayout,
            'lessor_base_amount' => $lessorBaseAmount,
            'platform_fee' => 0,
            'delivery_cost' => $deliveryCost,
            'total_amount' => $totalPayout,
            'start_date' => collect($items)->min('start_date'),
            'end_date' => collect($items)->max('end_date'),
            'platform_id' => Platform::getMain()->id,
            'contract_id' => $rentalCondition->contract_id ?? null,
            'rental_condition_id' => $rentalCondition->id,
            'shift_hours' => $rentalCondition->shift_hours ?? 8,
            'shifts_per_day' => $rentalCondition->shifts_per_day ?? 1,
            'payment_type' => $rentalCondition->payment_type ?? 'hourly',
            'transportation' => $rentalCondition->transportation ?? 'lessor',
            'fuel_responsibility' => $rentalCondition->fuel_responsibility ?? 'lessor',
            'delivery_location_id' => $rentalCondition->delivery_location_id ?? $firstItem->delivery_to_id,
            'delivery_from_id' => $firstItem->delivery_from_id,
            'delivery_to_id' => $firstItem->delivery_to_id,
            'delivery_type' => $deliveryType,
            'type' => 'proposal',
        ]);
    }

    /**
     * Создание позиции заказа для предложений
     */
    protected function createProposalOrderItem($childOrder, $cartItem)
    {
        $term = $cartItem->rentalTerm;
        $equipment = $term->equipment;
        $proposal = $cartItem->proposal;

        // 🔥 ИСПОЛЬЗУЕМ ФИКСИРОВАННЫЕ ЦЕНЫ ИЗ КОРЗИНЫ, А НЕ ПЕРЕСЧИТЫВАЕМ
        $customerPricePerHour = $cartItem->fixed_customer_price;
        $lessorPricePerHour = $cartItem->fixed_lessor_price;
        $platformFeePerHour = $cartItem->platform_fee;

        // 🔥 РАСЧЕТ ОБЩИХ СУММ НА ОСНОВЕ ФИКСИРОВАННЫХ ЦЕН
        $customerRentalTotal = $customerPricePerHour * $cartItem->period_count;
        $lessorRentalTotal = $lessorPricePerHour * $cartItem->period_count;
        $platformFeeTotal = $platformFeePerHour * $cartItem->period_count;

        // Рассчитываем discount_amount
        $itemTotal = $customerRentalTotal + $platformFeeTotal;
        $orderTotal = $childOrder->base_amount + $childOrder->platform_fee;
        $discountAmount = $orderTotal > 0 ? ($itemTotal / $orderTotal) * $childOrder->discount_amount : 0;

        \Log::debug('[CHECKOUT] Creating proposal order item', [
            'cart_item_id' => $cartItem->id,
            'customer_price_per_hour' => $customerPricePerHour,
            'lessor_price_per_hour' => $lessorPricePerHour,
            'platform_fee_per_hour' => $platformFeePerHour,
            'period_count' => $cartItem->period_count,
            'customer_rental_total' => $customerRentalTotal,
            'lessor_rental_total' => $lessorRentalTotal,
            'platform_fee_total' => $platformFeeTotal
        ]);

        return OrderItem::create([
            'order_id' => $childOrder->id,
            'equipment_id' => $equipment->id,
            'rental_term_id' => $term->id,
            'rental_condition_id' => $cartItem->rental_condition_id,
            'proposal_id' => $proposal->id,
            'quantity' => $proposal->proposed_quantity,
            'period_count' => $cartItem->period_count,
            // 🔥 ЦЕНЫ ДЛЯ АРЕНДАТОРА (С НАЦЕНКОЙ):
            'base_price' => $customerPricePerHour,
            'price_per_unit' => $customerPricePerHour,
            'fixed_customer_price' => $customerPricePerHour,
            'platform_fee' => $platformFeePerHour,
            // 🔥 ЦЕНЫ ДЛЯ АРЕНДОДАТЕЛЯ (БЕЗ НАЦЕНКИ):
            'fixed_lessor_price' => $lessorPricePerHour,
            // ДОСТАВКА И ИТОГИ:
            'delivery_cost' => $cartItem->delivery_cost,
            'distance_km' => $cartItem->distance_km ?? 0,
            'total_price' => $customerRentalTotal + $cartItem->delivery_cost - $discountAmount,
            'discount_amount' => $discountAmount,
            'delivery_from_id' => $cartItem->delivery_from_id,
            'delivery_to_id' => $cartItem->delivery_to_id,
            'lessor_company_id' => $childOrder->lessor_company_id,
            'status' => OrderItem::STATUS_PENDING,
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
        if (! $cartItem->rentalTerm || ! $cartItem->rentalTerm->equipment) {
            Log::error('Оборудование не найдено для элемента корзины', [
                'cart_item_id' => $cartItem->id,
                'rental_term_id' => $cartItem->rental_term_id,
            ]);
            throw new \Exception("Оборудование для позиции #{$cartItem->id} не найдено");
        }

        $equipment = $cartItem->rentalTerm->equipment;
        $startDate = Carbon::parse($cartItem->start_date);
        $endDate = Carbon::parse($cartItem->end_date);

        // Проверяем доступность
        if (! $this->availabilityService->isAvailable($equipment, $startDate, $endDate)) {
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
    }

    protected function getNextCompanyOrderNumber(?int $lesseeCompanyId, ?int $lessorCompanyId): int
    {
        // Определяем, для какой роли генерируем номер
        if (! is_null($lesseeCompanyId)) {
            // Если это заказ арендатора (родительский) - ищем макс. номер по lessee_company_id
            $lastOrder = Order::where('lessee_company_id', $lesseeCompanyId)
                ->orderBy('company_order_number', 'desc')
                ->first();
        } elseif (! is_null($lessorCompanyId)) {
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
