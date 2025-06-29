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

    public function addItem($rentalTermId, $periodCount, $basePrice, $platformFee)
    {
        $cart = $this->getCart();

        CartItem::updateOrCreate(
            [
                'cart_id' => $cart->id,
                'rental_term_id' => $rentalTermId
            ],
            [
                'period_count' => $periodCount,
                'base_price' => $basePrice,
                'platform_fee' => $platformFee
            ]
        );

        $this->recalculateTotals($cart);
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
