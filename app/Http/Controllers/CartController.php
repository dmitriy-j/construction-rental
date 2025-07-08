<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Models\EquipmentRentalTerm;
use Illuminate\Http\Request;
use App\Services\PricingService;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {
        $cart = $this->cartService->getCart();
        $cart->load('items.rentalTerm.equipment');
        
        return view('lessee.cart.index', [
            'cart' => $cart,
            'total' => $cart->total_base_amount + $cart->total_platform_fee - $cart->discount_amount
        ]);
    }

    public function add(EquipmentRentalTerm $rentalTerm, Request $request)
    {
        $request->validate([
            'period_count' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);

        $pricing = app(PricingService::class)->calculatePrice(
            $rentalTerm,
            auth()->user()->company,
            $request->period_count
        );

        $this->cartService->addItem(
            $rentalTerm->id,
            $request->period_count,
            $pricing['base_price_per_unit'],
            $pricing['platform_fee_per_unit']
        );

        return back()->with('success', 'Оборудование добавлено в корзину');
    }

    public function remove($itemId)
    {
        $this->cartService->removeItem($itemId);
        return back()->with('success', 'Позиция удалена из корзины');
    }

    public function updateDates(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date'
        ]);

        $this->cartService->setDates($request->start_date, $request->end_date);
        return back()->with('success', 'Даты аренды обновлены');
    }

    public function clear()
    {
        $this->cartService->clearCart();
        return back()->with('success', 'Корзина очищена');
    }
}