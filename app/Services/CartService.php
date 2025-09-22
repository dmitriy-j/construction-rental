<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RentalRequestResponse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CartService
{
    public function getCart()
    {
        return Cart::firstOrCreate(['user_id' => Auth::id()]);
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
        $cart->load('items');

        $cart->total_base_amount = $cart->items->sum(function ($item) {
            return $item->base_price * $item->period_count;
        });

        $cart->total_platform_fee = $cart->items->sum(function ($item) {
            return $item->platform_fee * $item->period_count;
        });

        $cart->discount_amount = 0;
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
