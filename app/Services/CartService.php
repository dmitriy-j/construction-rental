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

            \Log::debug('Adding item to cart', [
                'cart_id' => $cart->id,
                'rental_term_id' => $rentalTermId,
                'period_count' => $periodCount,
                'base_price' => $basePrice,
                'platform_fee' => $platformFee
            ]);

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
                    'delivery_cost' => $deliveryCost
                ]
            );

            $this->recalculateTotals($cart);

            \Log::info('Item added successfully', [
                'cart_id' => $cart->id,
                'rental_term_id' => $rentalTermId
            ]);

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

        // Обновляем даты для всех элементов корзины
        foreach ($cart->items as $item) {
            $item->update([
                'start_date' => Carbon::parse($startDate),
                'end_date' => Carbon::parse($endDate)
            ]);
        }

        // Пересчитываем периоды для всех элементов
        foreach ($cart->items as $item) {
            $periodCount = $item->rentalTerm->calculatePeriodCount(
                $startDate,
                $endDate
            );

            $item->update(['period_count' => $periodCount]);
        }

        $this->recalculateTotals($cart);
    }

    public function recalculateTotals(Cart $cart)
    {
        $cart->load('items');

        $cart->total_base_amount = $cart->items->sum(function ($item) {
            return $item->base_price * $item->period_count;
        });

        $cart->total_platform_fee = $cart->items->sum(function ($item) {
            return $item->platform_fee * $item->period_count;
        });

        $cart->discount_amount = 0; // Рассчитывается при оформлении
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

    public function updateSelectedItems(array $itemIds, $startDate, $endDate)
    {
        foreach ($itemIds as $id) {
            $this->updateItemDates($id, $startDate, $endDate);
        }
    }

    public function removeSelectedItems(array $itemIds)
    {
        CartItem::whereIn('id', $itemIds)->delete();
        $this->recalculateTotals($this->getCart());
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
}
