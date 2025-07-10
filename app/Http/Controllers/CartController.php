<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Models\EquipmentRentalTerm;
use Illuminate\Http\Request;
use App\Services\PricingService;
use App\Services\EquipmentAvailabilityService; // Добавлен импорт

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
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);

        // Проверяем аутентификацию
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Пожалуйста, войдите в систему');
        }

        if (!auth()->user()->company) {
            return back()->with('error', 'Ваш профиль не привязан к компании');
        }

        // Рассчитываем количество периодов
        $periodCount = $rentalTerm->calculatePeriodCount(
            $request->start_date,
            $request->end_date
        );

        // Проверяем минимальный период аренды
        if ($periodCount < 1) {
            return back()->with('error', 'Минимальный период аренды: 1 ' . $rentalTerm->period);
        }

        // Проверка доступности оборудования
        $availabilityService = app(EquipmentAvailabilityService::class); // Исправленный вызов
        if (!$availabilityService->isAvailable(
            $rentalTerm->equipment,
            $request->start_date,
            $request->end_date
        )) {
            return back()->withErrors([
                'availability' => 'Оборудование недоступно на выбранные даты'
            ]);
        }

        $pricing = app(PricingService::class)->calculatePrice(
            $rentalTerm,
            auth()->user()->company,
            $periodCount
        );

        $this->cartService->addItem(
            $rentalTerm->id,
            $periodCount,
            $pricing['base_price_per_unit'],
            $pricing['platform_fee_per_unit'],
            $request->start_date,
            $request->end_date
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
