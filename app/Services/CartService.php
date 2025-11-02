<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RentalRequestResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CartService
{
    public function getCart(string $type = Cart::TYPE_REGULAR): Cart
    {
        return Cart::getByType(Auth::id(), $type);
    }

     /**
     * Добавление подтвержденного предложения в корзину
     */
     public function addProposalItem(int $proposalId): CartItem
    {
        return DB::transaction(function () use ($proposalId) {
            $proposal = RentalRequestResponse::with(['equipment.rentalTerms', 'rentalRequest'])
                ->findOrFail($proposalId);

            // Проверяем, что пользователь является владельцем заявки
            if ($proposal->rentalRequest->user_id !== auth()->id()) {
                throw new \Exception('У вас нет прав для принятия этого предложения');
            }

            // Проверяем, что предложение еще действительно
            if ($proposal->expires_at && $proposal->expires_at->isPast()) {
                throw new \Exception('Срок действия предложения истек');
            }

            // Получаем или создаем корзину для предложений
            $cart = Cart::getByType(auth()->id(), Cart::TYPE_PROPOSAL);

            // Если у корзины нет rental_request_id, устанавливаем его
            if (!$cart->rental_request_id) {
                $cart->update([
                    'rental_request_id' => $proposal->rental_request_id,
                    'reserved_until' => now()->addHours(24),
                    'reservation_token' => Str::uuid(),
                ]);
            } else {
                // Продлеваем резервирование существующей корзины
                $cart->update([
                    'reserved_until' => now()->addHours(24),
                ]);
            }

            // Проверяем, не добавлено ли уже это предложение
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('proposal_id', $proposalId)
                ->first();

            if ($existingItem) {
                throw new \Exception('Это предложение уже добавлено в корзину');
            }

            // Резервируем количество в заявке
            $this->reserveRequestQuantity($proposal);

            // Создаем элемент корзины
            $cartItem = CartItem::createFromProposal($proposal, $cart);

            // 🔥 ДОБАВЛЯЕМ ПРОВЕРКУ МЕТОДА calculateActualWorkingHours
            if (method_exists($cartItem, 'calculateActualWorkingHours')) {
                $workingHours = $cartItem->calculateActualWorkingHours();
                \Log::info('Working hours calculated successfully', ['hours' => $workingHours]);
            } else {
                \Log::warning('Метод calculateActualWorkingHours не существует в CartItem, используем fallback');
                $workingHours = $this->calculateFallbackWorkingHours($cartItem);
            }

            $this->recalculateTotals($cart);

            // Временное резервирование оборудования
            $availabilityService = app(EquipmentAvailabilityService::class);
            $availabilityService->bookEquipment(
                $proposal->equipment,
                $proposal->rentalRequest->rental_period_start,
                $proposal->rentalRequest->rental_period_end,
                null, // order_id будет установлен позже
                EquipmentAvailabilityService::STATUS_TEMP_RESERVE // Используем константу
            );

            $this->recalculateTotals($cart);

            return $cartItem;
        });
    }

     /**
     * 🔥 FALLBACK РАСЧЕТ РАБОЧИХ ЧАСОВ
     */
    private function calculateFallbackWorkingHours(CartItem $cartItem): int
    {
        if (!$cartItem->start_date || !$cartItem->end_date) {
            return 0;
        }

        $start = Carbon::parse($cartItem->start_date);
        $end = Carbon::parse($cartItem->end_date);
        $days = $start->diffInDays($end) + 1;

        // Стандартные рабочие часы
        return $days * 8; // 8 часов в день
    }

     /**
     * Резервирование количества в заявке
     */
    private function reserveRequestQuantity(RentalRequestResponse $proposal): void
    {
        if ($proposal->rental_request_item_id) {
            $requestItem = RentalRequestItem::find($proposal->rental_request_item_id);
            if ($requestItem) {
                $requestItem->increment('reserved_quantity', $proposal->proposed_quantity);
            }
        }
    }

    public function fixProposalCartPrices(): void
    {
        $proposalCart = $this->getCart(Cart::TYPE_PROPOSAL);

        foreach ($proposalCart->items as $item) {
            if ($item->is_proposal_item && $item->proposal) {
                $proposal = $item->proposal;
                $rentalRequest = $proposal->rentalRequest;

                // Пересчитываем часы
                $start = \Carbon\Carbon::parse($rentalRequest->rental_period_start);
                $end = \Carbon\Carbon::parse($rentalRequest->rental_period_end);
                $days = $start->diffInDays($end) + 1;

                $rentalConditions = json_decode($rentalRequest->rental_conditions, true);
                $shiftHours = $rentalConditions['hours_per_shift'] ?? 8;
                $shiftsPerDay = $rentalConditions['shifts_per_day'] ?? 1;

                $workingHours = $days * $shiftHours * $shiftsPerDay;

                // Получаем цены из price_breakdown
                $priceBreakdown = $proposal->price_breakdown;
                if (is_string($priceBreakdown)) {
                    $priceBreakdown = json_decode($priceBreakdown, true);
                }

                $customerPricePerHour = null;
                $lessorPricePerHour = null;

                if (isset($priceBreakdown['items']) && is_array($priceBreakdown['items'])) {
                    foreach ($priceBreakdown['items'] as $itemData) {
                        if ($itemData['equipment_id'] == $proposal->equipment_id) {
                            $customerPricePerHour = $itemData['customer_price_per_unit'] ?? null;
                            $lessorPricePerHour = $itemData['lessor_price_per_unit'] ?? null;
                            break;
                        }
                    }
                } else {
                    $customerPricePerHour = $priceBreakdown['customer_price_per_unit'] ?? null;
                    $lessorPricePerHour = $priceBreakdown['lessor_price_per_unit'] ?? null;
                }

                // Если цены не найдены, используем расчет по умолчанию
                if (!$customerPricePerHour || !$lessorPricePerHour) {
                    $customerPricePerHour = $proposal->proposed_price / $workingHours;
                    $lessorPricePerHour = $customerPricePerHour - 100;
                }

                $platformFeePerHour = $customerPricePerHour - $lessorPricePerHour;
                $totalPlatformFee = $platformFeePerHour * $workingHours;

                // Обновляем запись
                $item->update([
                    'period_count' => $workingHours,
                    'base_price' => $customerPricePerHour,
                    'fixed_customer_price' => $customerPricePerHour,
                    'fixed_lessor_price' => $lessorPricePerHour,
                    'platform_fee' => $platformFeePerHour,
                    'proposal_data' => array_merge($item->proposal_data ?? [], [
                        'total_working_hours' => $workingHours,
                        'customer_price_per_hour' => $customerPricePerHour,
                        'lessor_price_per_hour' => $lessorPricePerHour,
                        'platform_fee_per_hour' => $platformFeePerHour,
                        'total_platform_fee' => $totalPlatformFee,
                    ])
                ]);
            }
        }
    }

    public function getProposalCartWithDelivery(): Cart
    {
        $cart = $this->getCart(Cart::TYPE_PROPOSAL);

        $cart->load([
            'items.proposal.equipment.mainImage',
            'items.proposal.equipment.location',
            'items.rentalRequestItem',
            'rentalRequest',
            'rentalRequest.location'
        ]);

        // 🔥 ГАРАНТИРУЕМ НАЛИЧИЕ ДАННЫХ О ДОСТАВКЕ
        foreach ($cart->items as $item) {
            if ($item->is_proposal_item && $item->proposal) {
                $this->ensureDeliveryData($item);
            }
        }

        return $cart;
    }

    private function ensureDeliveryData(CartItem $item): void
    {
        try {
            $proposal = $item->proposal;
            $priceBreakdown = $proposal->price_breakdown;

            if (is_string($priceBreakdown)) {
                $priceBreakdown = json_decode($priceBreakdown, true);
            }

            // Если в proposal_data нет delivery данных, но они есть в price_breakdown
            if (empty($item->proposal_data['delivery_breakdown']) && !empty($priceBreakdown)) {
                $deliveryBreakdown = $priceBreakdown['delivery_breakdown'] ?? [];
                $hasDelivery = $deliveryBreakdown['delivery_required'] ?? false;
                $deliveryCost = $deliveryBreakdown['delivery_cost'] ?? 0;

                // Для bulk-предложений
                if (isset($priceBreakdown['items']) && is_array($priceBreakdown['items'])) {
                    foreach ($priceBreakdown['items'] as $pbItem) {
                        if ($pbItem['equipment_id'] == $proposal->equipment_id) {
                            $deliveryBreakdown = $pbItem['delivery_breakdown'] ?? [];
                            $hasDelivery = $deliveryBreakdown['delivery_required'] ?? false;
                            $deliveryCost = $deliveryBreakdown['delivery_cost'] ?? 0;
                            break;
                        }
                    }
                }

                // Обновляем элемент корзины
                $item->update([
                    'delivery_cost' => $deliveryCost,
                    'proposal_data' => array_merge($item->proposal_data ?? [], [
                        'delivery_breakdown' => $deliveryBreakdown,
                        'has_delivery' => $hasDelivery,
                        'delivery_cost' => $deliveryCost,
                        'delivery_ensured_at' => now()->toDateTimeString()
                    ])
                ]);

                \Log::info('Ensured delivery data for cart item', [
                    'item_id' => $item->id,
                    'proposal_id' => $proposal->id,
                    'has_delivery' => $hasDelivery,
                    'delivery_cost' => $deliveryCost
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error ensuring delivery data: ' . $e->getMessage(), [
                'item_id' => $item->id,
                'proposal_id' => $item->proposal_id
            ]);
        }
    }

    public function getProposalCartWithGuarantee(): Cart
    {
        $cart = $this->getCart(Cart::TYPE_PROPOSAL);

        if (!$cart) {
            throw new \Exception('Корзина предложений не найдена');
        }

        // Гарантируем загрузку отношений
        $cart->load([
            'items.proposal.equipment.mainImage',
            'items.proposal.equipment.location',
            'items.rentalRequestItem',
            'rentalRequest',
            'rentalRequest.location'
        ]);

        return $cart;
    }

    /**
     * 🔥 ДОПОЛНЯЕМ ЭЛЕМЕНТ КОРЗИНЫ ДАННЫМИ ДОСТАВКИ
     */
    private function enhanceCartItemWithDeliveryData(CartItem $item): void
    {
        try {
            $proposal = $item->proposal;
            $priceBreakdown = $proposal->price_breakdown;

            if (is_string($priceBreakdown)) {
                $priceBreakdown = json_decode($priceBreakdown, true);
            }

            // 🔥 ГАРАНТИРУЕМ НАЛИЧИЕ delivery_breakdown
            $deliveryBreakdown = $priceBreakdown['delivery_breakdown'] ?? [
                'delivery_required' => false,
                'delivery_cost' => 0,
                'distance_km' => 0,
                'vehicle_type' => null
            ];

            // Обновляем proposal_data с информацией о доставке
            $item->proposal_data = array_merge($item->proposal_data ?? [], [
                'delivery_breakdown' => $deliveryBreakdown,
                'has_delivery' => $deliveryBreakdown['delivery_required'] ?? false,
                'delivery_cost' => $deliveryBreakdown['delivery_cost'] ?? 0
            ]);

        } catch (\Exception $e) {
            \Log::error('Error enhancing cart item with delivery data: ' . $e->getMessage());

            $item->proposal_data = array_merge($item->proposal_data ?? [], [
                'delivery_breakdown' => [
                    'delivery_required' => false,
                    'delivery_cost' => 0,
                    'distance_km' => 0,
                    'vehicle_type' => null
                ],
                'has_delivery' => false,
                'delivery_cost' => 0
            ]);
        }
    }

    /**
     * Освобождение резервирования
     */
     public function releaseReservation(CartItem $item): void
    {
        if ($item->is_proposal_item && $item->rental_request_item_id) {
            $requestItem = RentalRequestItem::find($item->rental_request_item_id);
            if ($requestItem) {
                $requestItem->decrement('reserved_quantity', $item->proposed_quantity ?? 1);
            }

            // Освобождаем резервирование оборудования
            if ($item->proposal && $item->proposal->equipment) {
                $availabilityService = app(EquipmentAvailabilityService::class);
                $availabilityService->releaseEquipmentReservation(
                    $item->proposal->equipment,
                    $item->start_date,
                    $item->end_date
                );
            }
        }
    }

    /**
     * Продление резервирования для корзины с предложениями
     */
    public function extendProposalReservation(): bool
    {
        $cart = $this->getCart(Cart::TYPE_PROPOSAL);

        if (!$cart->is_reservation_active) {
            return false;
        }

        return $cart->extendReservation();
    }

    /**
     * Получение прогресса заполнения заявки
     */
    public function getRequestProgress(int $rentalRequestId): array
    {
        $request = \App\Models\RentalRequest::with('items')->find($rentalRequestId);

        $totalItems = $request->items->count();
        $completedItems = $request->items->filter(function ($item) {
            return $item->reserved_quantity >= $item->quantity;
        })->count();

        $progress = $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0;

        return [
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'progress_percentage' => round($progress, 2),
            'is_complete' => $progress >= 100,
        ];
    }

    public function addItem(
        int $rentalTermId,
        int $periodCount,
        float $basePrice,
        float $platformFee,
        string $startDate,
        string $endDate,
        ?int $rentalConditionId = null,
        ?int $deliveryFromId = null,
        ?int $deliveryToId = null,
        float $deliveryCost = 0
    ): CartItem {
        $cart = $this->getCart();

        return $cart->items()->create([
            'rental_term_id' => $rentalTermId,
            'period_count' => $periodCount,
            'base_price' => $basePrice,
            'platform_fee' => $platformFee,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rental_condition_id' => $rentalConditionId,
            'delivery_from_id' => $deliveryFromId,
            'delivery_to_id' => $deliveryToId,
            'delivery_cost' => $deliveryCost,
        ]);
    }

     // Существующие методы остаются, но добавляем проверку типа корзины
    public function removeItem($itemId)
    {
        $item = CartItem::findOrFail($itemId);
        $cart = $item->cart;

        // Освобождаем резервирование для предложений
        if ($item->is_proposal_item) {
            $this->releaseReservation($item);
        }

        $item->delete();
        $this->recalculateTotals($cart);
    }

    public function setDates($startDate, $endDate)
    {
        $cart = $this->getCart();
        $cart->update([
            'start_date' => Carbon::parse($startDate),
            'end_date' => Carbon::parse($endDate),
        ]);

        $cart->load('items.rentalTerm.equipment', 'items.rentalCondition');

        foreach ($cart->items as $item) {
            $days = $startDate->diffInDays($endDate) + 1;
            $workingHours = $days * $item->rentalCondition->shift_hours * $item->rentalCondition->shifts_per_day;

            $pricing = app(PricingService::class)->calculatePrice(
                $item->rentalTerm,
                auth()->user()->company,
                $workingHours,
                $item->rentalCondition
            );

            $item->update([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'period_count' => $workingHours,
                'base_price' => $pricing['base_price_per_unit'],
                'platform_fee' => $pricing['platform_fee'],
            ]);
        }

        $this->recalculateTotals($cart);
    }

    public function recalculateTotals(Cart $cart)
    {
        \Log::debug('[CART_SERVICE] Recalculating totals for cart', [
            'cart_id' => $cart->id,
            'cart_type' => $cart->type,
            'items_count' => $cart->items->count()
        ]);

        $cart->load('items');

        // 🔥 ПРАВИЛЬНЫЙ РАСЧЕТ: base_price * period_count для каждого элемента
        $cart->total_base_amount = $cart->items->sum(function ($item) {
            $total = $item->base_price * $item->period_count;
            \Log::debug('[CART_SERVICE] Item calculation', [
                'item_id' => $item->id,
                'base_price' => $item->base_price,
                'period_count' => $item->period_count,
                'total' => $total
            ]);
            return $total;
        });

        // 🔥 ПРАВИЛЬНЫЙ РАСЧЕТ: platform_fee * period_count
        $cart->total_platform_fee = $cart->items->sum(function ($item) {
            return $item->platform_fee * $item->period_count;
        });

        $cart->discount_amount = 0;

        \Log::debug('[CART_SERVICE] Final totals', [
            'total_base_amount' => $cart->total_base_amount,
            'total_platform_fee' => $cart->total_platform_fee
        ]);

        $cart->save();
    }

    public function updateDates(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $cart = $this->getCart();
        $cart->update([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        foreach ($cart->items as $item) {
            $item->update([
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
        }

        return back()->with('success', 'Даты аренды обновлены');
    }

    public function updateItemDates($itemId, $startDate, $endDate)
    {
        $item = CartItem::findOrFail($itemId);

        $item->update([
            'start_date' => Carbon::parse($startDate),
            'end_date' => Carbon::parse($endDate),
            'period_count' => $item->rentalTerm->calculatePeriodCount($startDate, $endDate),
        ]);

        $this->recalculateTotals($item->cart);
    }

    public function updateSelectedItems(array $itemIds, Carbon $startDate, Carbon $endDate)
    {
        $cart = $this->getCart();
        $cart->load('items.rentalCondition');

        foreach ($itemIds as $id) {
            $item = CartItem::find($id);
            if ($item && $item->cart_id === $cart->id && $item->rentalCondition) {
                $days = $startDate->diffInDays($endDate) + 1;
                $workingHours = $days * $item->rentalCondition->shift_hours * $item->rentalCondition->shifts_per_day;

                $pricing = app(PricingService::class)->calculatePrice(
                    $item->rentalTerm,
                    auth()->user()->company,
                    $workingHours,
                    $item->rentalCondition
                );

                $item->update([
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'period_count' => $workingHours,
                    'base_price' => $pricing['base_price_per_unit'],
                    'platform_fee' => $pricing['platform_fee'],
                ]);
            }
        }

        $this->recalculateTotals($cart);
    }

    public function removeSelectedItems(array $itemIds)
    {
        if (empty($itemIds)) {
            return response()->json(['success' => false, 'message' => 'No items selected']);
        }

        $cart = $this->getCart();
        $deleted = $cart->items()->whereIn('id', $itemIds)->delete();

        $this->recalculateTotals($cart);

        return response()->json([
            'success' => true,
            'deleted_count' => $deleted,
            'cart' => $this->getCart()->load('items'),
        ]);
    }

    public function clearCart()
    {
        $cart = $this->getCart();
        $cart->items()->delete();
        $cart->update([
            'total_base_amount' => 0,
            'total_platform_fee' => 0,
            'discount_amount' => 0,
        ]);
    }

    public function updatePeriodCount(CartItem $item)
    {
        $start = Carbon::parse($item->start_date);
        $end = Carbon::parse($item->end_date);
        $days = $start->diffInDays($end) + 1;

        return $days * $item->rentalCondition->shift_hours * $item->rentalCondition->shifts_per_day;
    }

    /**
     * СОЗДАНИЕ ЗАКАЗА ИЗ ПРЕДЛОЖЕНИЯ (НОВАЯ ФУНКЦИОНАЛЬНОСТЬ)
     */
    public function createOrderFromProposal(array $proposalData, User $user): Order
    {
        $proposal = RentalRequestResponse::with(['rentalRequest', 'equipment', 'lessor.company'])
            ->findOrFail($proposalData['response_id']);

        // Проверяем, что пользователь является владельцем заявки
        if ($proposal->rentalRequest->user_id !== $user->id) {
            throw new \Exception('У вас нет прав для принятия этого предложения');
        }

        // Проверяем, что предложение еще действительно
        if ($proposal->expires_at && $proposal->expires_at->isPast()) {
            throw new \Exception('Срок действия предложения истек');
        }

        // Создаем заказ
        $order = Order::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'lessor_company_id' => $proposal->lessor->company_id,
            'status' => 'pending_approval',
            'total_amount' => $proposalData['final_price'] ?? $proposal->proposed_price,
            'request_response_id' => $proposal->id,
            'rental_period_start' => $proposal->rentalRequest->rental_period_start,
            'rental_period_end' => $proposal->rentalRequest->rental_period_end,
            'delivery_required' => $proposal->rentalRequest->delivery_required,
            'location_id' => $proposal->rentalRequest->location_id,
        ]);

        // Создаем элементы заказа
        OrderItem::create([
            'order_id' => $order->id,
            'equipment_id' => $proposal->equipment_id,
            'rental_term_id' => $proposal->equipment->rentalTerms->first()->id,
            'quantity' => 1,
            'unit_price' => $proposalData['final_price'] ?? $proposal->proposed_price,
            'total_price' => $proposalData['final_price'] ?? $proposal->proposed_price,
            'rental_period_start' => $proposal->rentalRequest->rental_period_start,
            'rental_period_end' => $proposal->rentalRequest->rental_period_end,
            'rental_condition_id' => $proposalData['rental_condition_id'] ?? null,
        ]);

        // Обновляем статус предложения
        $proposal->update(['status' => 'accepted']);

        // Обновляем статус заявки
        $proposal->rentalRequest->update(['status' => 'processing']);

        return $order;
    }

    /**
     * РАСЧЕТ СТОИМОСТИ ДЛЯ ПРЕДЛОЖЕНИЯ
     */
    public function calculateProposalCost(RentalRequestResponse $proposal): array
    {
        $pricingService = app(PricingService::class);

        // Получаем условия аренды для расчета
        $rentalTerm = $proposal->equipment->rentalTerms->first();
        $workingHours = $this->calculateWorkingHours(
            $proposal->rentalRequest->rental_period_start,
            $proposal->rentalRequest->rental_period_end
        );

        // Расчет стоимости через PricingService
        $pricing = $pricingService->calculatePrice(
            $rentalTerm,
            $proposal->rentalRequest->user->company,
            $workingHours,
            $proposal->additional_terms ? json_decode($proposal->additional_terms, true) : []
        );

        return [
            'proposed_price' => $proposal->proposed_price,
            'calculated_price' => $pricing['final_price'],
            'platform_fee' => $pricing['platform_fee'],
            'discount' => $pricing['discount_amount'],
            'working_hours' => $workingHours,
            'is_within_budget' => $proposal->proposed_price >= $proposal->rentalRequest->budget_from &&
                                 $proposal->proposed_price <= $proposal->rentalRequest->budget_to
        ];
    }

    private function calculateWorkingHours($startDate, $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $start->diffInDays($end) + 1;

        // Стандартные рабочие часы (можно сделать настраиваемыми)
        return $days * 8; // 8 часов в день
    }

    public static function getCartItemsCount(User $user): int
    {
        $cart = \App\Models\Cart::where('user_id', $user->id)->first();
        return $cart ? $cart->items()->count() : 0;
    }
}
