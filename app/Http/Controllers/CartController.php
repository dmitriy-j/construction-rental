<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function checkout(Request $request)
    {
        $cart = $this->cartService->getCart()->load('items.equipmentRentalTerm.equipment');

        if ($cart->items->isEmpty()) {
            return redirect()->back()->with('error', 'Корзина пуста');
        }

        // Проверяем, что вся техника принадлежит одному арендодателю
        $lessorCompanyId = $this->validateSingleLessor($cart);

        // Используем транзакцию для безопасности данных
        $order = DB::transaction(function () use ($cart, $lessorCompanyId) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'lessee_company_id' => auth()->user()->company_id,
                'lessor_company_id' => $lessorCompanyId,
                'base_amount' => $cart->total_base_amount,
                'platform_fee' => $cart->total_platform_fee,
                'discount_amount' => $cart->discount_amount,
                'total_amount' => $cart->total_base_amount + $cart->total_platform_fee - $cart->discount_amount,
                'status' => Order::STATUS_PENDING
            ]);

            // Переносим элементы корзины в заказ
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'equipment_rental_term_id' => $item->equipment_rental_term_id,
                    'period_count' => $item->period_count,
                    'base_price' => $item->base_price,
                    'platform_fee' => $item->platform_fee
                ]);
            }

            return $order;
        });

        $this->cartService->clearCart();
        return redirect()->route('orders.show', $order)->with('success', 'Заказ успешно оформлен!');
    }

    /**
     * Проверяет, что все позиции в корзине принадлежат одному арендодателю
     *
     * @throws \Exception
     */
    protected function validateSingleLessor(Cart $cart): int
    {
        $lessorCompanyId = null;

        foreach ($cart->items as $item) {
            $currentLessorId = $item->equipmentRentalTerm->equipment->company_id;

            if ($lessorCompanyId === null) {
                $lessorCompanyId = $currentLessorId;
            } elseif ($lessorCompanyId != $currentLessorId) {
                throw new \Exception('Все позиции в заказе должны принадлежать одному арендодателю');
            }
        }

        return $lessorCompanyId;
    }

    public function setDates(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date'
        ]);

        $cartService = app(CartService::class);
        $cartService->setDates($request->start_date, $request->end_date);

        return back()->with('success', 'Даты аренды обновлены');
    }
}
