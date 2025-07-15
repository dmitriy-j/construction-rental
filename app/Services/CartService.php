<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class CartService
{
    public function getCart()
    {
        return Cart::firstOrCreate(['user_id' => Auth::id()]);
    }

    public function addItem(
        $rentalTermId,
        $periodCount,
        $basePrice,
        $platformFee,
        $startDate = null,
        $endDate = null,
        $rentalConditionId = null,
        $deliveryFromId = null,
        $deliveryToId = null,
        $deliveryCost = 0
    ) {
        try {
            $cart = $this->getCart();

            CartItem::updateOrCreate(
                [
                    'cart_id' => $cart->id,
                    'rental_term_id' => $rentalTermId
                ],
                [
                    'period_count' => $periodCount,
                    'base_price' => $basePrice,
                    'platform_fee' => $platformFee,
                    'start_date' => $startDate ? Carbon::parse($startDate) : null,
                    'end_date' => $endDate ? Carbon::parse($endDate) : null,
                    'rental_condition_id' => $rentalConditionId,
                    'delivery_from_id' => $deliveryFromId,
                    'delivery_to_id' => $deliveryToId,
                    // Сохраняем стоимость доставки в cart_item
                    'delivery_cost' => $deliveryCost
                ]
            );

            $this->recalculateTotals($cart);

        } catch (\Exception $e) {
            \Log::error('Error in addItem: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            throw $e;
        }
    }

    public function removeItem($itemId)
    {
        $cart = $this->getCart();
        $cart->items()->where('id', $itemId)->delete();
        $this->recalculateTotals($cart);
    }

    public function setDates($startDate, $endDate)
{
    $cart = $this->getCart();
    $cart->update([
        'start_date' => Carbon::parse($startDate),
        'end_date' => Carbon::parse($endDate)
    ]);

    // Загружаем связанные данные
    $cart->load('items.rentalTerm.equipment', 'items.rentalCondition');

    foreach ($cart->items as $item) {
        $days = $startDate->diffInDays($endDate);

        // Пересчет рабочих часов
        $workingHours = $days * $item->rentalCondition->shift_hours * $item->rentalCondition->shifts_per_day;

        // Пересчет стоимости
        $pricing = app(PricingService::class)->calculatePrice(
            $item->rentalTerm,
            auth()->user()->company,
            $workingHours,
            $item->rentalCondition
        );

        // Обновляем данные элемента
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
        $cart->load('items');

        // Сумма аренды (без доставки)
        $cart->total_base_amount = $cart->items->sum(function ($item) {
            return $item->base_price * $item->period_count;
        });

        // Общая платформенная комиссия
        $cart->total_platform_fee = $cart->items->sum(function ($item) {
            return $item->platform_fee * $item->period_count;
        });

        // Убираем total_delivery_cost, так как доставка хранится в DeliveryNote
        $cart->discount_amount = 0;

        $cart->save();
    }

    public function updateDates(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $cart = $this->getCart();
        $cart->update([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        foreach ($cart->items as $item) {
            $item->update([
                'start_date' => $startDate,
                'end_date' => $endDate
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
            'period_count' => $item->rentalTerm->calculatePeriodCount($startDate, $endDate)
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
                // Правильный расчёт дней (1 день для 15-16 июля)
                $days = $startDate->diffInDays($endDate);

                // Пересчёт рабочих часов
                $workingHours = $days * $item->rentalCondition->shift_hours * $item->rentalCondition->shifts_per_day;

                // Пересчёт стоимости через PricingService
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
                    // delivery_cost не изменяется
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
            'cart' => $this->getCart()->load('items') // Добавляем обновлённую корзину
        ]);
    }

    public function clearCart()
    {
        $cart = $this->getCart();
        $cart->items()->delete();
        $cart->update([
            'total_base_amount' => 0,
            'total_platform_fee' => 0,
            'discount_amount' => 0
        ]);
    }

    public function updatePeriodCount(CartItem $item)
    {
        $start = Carbon::parse($item->start_date);
        $end = Carbon::parse($item->end_date);
        $days = $start->diffInDays($end);

        return $days * $item->rentalCondition->shift_hours * $item->rentalCondition->shifts_per_day;
    }
}
