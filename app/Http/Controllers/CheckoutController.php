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
        Log::info('[CHECKOUT] Begin checkout', ['user_id' => auth()->id(), 'selected_items' => $request->input('selected_items', '')]);

        try {
            $cart = $this->cartService->getCart();
            $selectedItems = $request->input('selected_items', '');
            $selectedItems = json_decode($selectedItems, true) ?? [];

            if (empty($selectedItems)) {
                $selectedItems = $cart->items->pluck('id')->toArray();
            }

            $cart->load(['items.rentalTerm.equipment.specifications', 'items.rentalTerm.equipment.company', 'items.rentalCondition', 'items.deliveryFrom', 'items.deliveryTo']);
            $cartItems = $cart->items->filter(fn($item) => in_array($item->id, $selectedItems));

            if ($cartItems->isEmpty()) {
                return redirect()->back()->with('error', 'Корзина пуста');
            }

            DB::beginTransaction();
            try {
                $nextOrderNumber = $this->getNextCompanyOrderNumber(auth()->user()->company_id, null);
                $parentOrder = $this->createRegularParentOrder($cartItems, $nextOrderNumber);

                $groupedItems = $cartItems->groupBy(fn($item) => $item->rentalTerm->equipment->company_id);

                foreach ($groupedItems as $companyId => $items) {
                    $lessorOrderNumber = $this->getNextCompanyOrderNumber(null, $companyId);
                    $childOrder = $this->createRegularChildOrder($parentOrder->id, $items, $companyId, $lessorOrderNumber);
                    $parentOrder->childOrders()->save($childOrder);

                    foreach ($items as $item) {
                        $this->bookEquipmentItem($item, $childOrder->id);
                    }
                }

                $cart->items()->whereIn('id', $selectedItems)->delete();
                $this->cartService->recalculateTotals($cart);
                DB::commit();

                Log::info('[CHECKOUT] Order created', ['parent_order_id' => $parentOrder->id]);
                return redirect()->route('lessee.orders.show', ['order' => $parentOrder->id])
                    ->with('success', 'Заказ #' . $parentOrder->company_order_number . ' успешно оформлен!');
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('[CHECKOUT] Transaction error: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Ошибка: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            Log::error('[CHECKOUT] Critical error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ошибка: ' . $e->getMessage());
        }
    }

    protected function createRegularParentOrder($cartItems, $orderNumber)
    {
        if ($cartItems->isEmpty()) throw new \Exception('Нет позиций');

        $deliveryCost = $cartItems->sum('delivery_cost');
        $customerRentalTotal = $cartItems->sum(fn($i) => $i->base_price * $i->period_count);
        $platformFeeTotal = $cartItems->sum('platform_fee');
        $lessorBaseAmount = $cartItems->sum(fn($i) => ($i->fixed_lessor_price ?? ($i->base_price - ($i->platform_fee ?? 0))) * $i->period_count);

        return Order::create([
            'user_id' => auth()->id(),
            'lessee_company_id' => auth()->user()->company_id,
            'lessor_company_id' => null,
            'status' => Order::STATUS_AGGREGATED,
            'company_order_number' => $orderNumber,
            'total_amount' => $customerRentalTotal + $deliveryCost,
            'base_amount' => $customerRentalTotal,
            'platform_fee' => $platformFeeTotal,
            'lessor_base_amount' => $lessorBaseAmount,
            'delivery_cost' => $deliveryCost,
            'start_date' => $cartItems->min('start_date'),
            'end_date' => $cartItems->max('end_date'),
            'delivery_type' => Order::DELIVERY_PICKUP,
            'type' => 'regular',
        ]);
    }

    private function createRegularChildOrder(int $parentOrderId, $items, int $lessorCompanyId, int $orderNumber): Order
    {
        $totalAmount = 0;
        $orderItems = [];

        foreach ($items as $cartItem) {
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
                'platform_fee' => $cartItem->platform_fee ?? 0,
                'period_count' => $cartItem->period_count,
                'delivery_cost' => $cartItem->delivery_cost ?? 0,
                'total_price' => $itemTotal,
                'fixed_lessor_price' => $cartItem->fixed_lessor_price ?? ($cartItem->base_price - ($cartItem->platform_fee ?? 0)),
                'fixed_customer_price' => $cartItem->fixed_customer_price ?? $cartItem->base_price,
                'proposal_id' => null,
                'start_date' => $cartItem->start_date,
                'end_date' => $cartItem->end_date,
                'discount_amount' => 0,
                'distance_km' => $cartItem->distance_km ?? 0,
                'status' => OrderItem::STATUS_PENDING,
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

        foreach ($orderItems as $itemData) {
            $childOrder->items()->create($itemData);
        }

        return $childOrder;
    }

    public function bookEquipmentItem($cartItem, $orderId)
    {
        if (!$cartItem->rentalTerm || !$cartItem->rentalTerm->equipment) {
            throw new \Exception("Оборудование для позиции #{$cartItem->id} не найдено");
        }

        $equipment = $cartItem->rentalTerm->equipment;
        $startDate = Carbon::parse($cartItem->start_date);
        $endDate = Carbon::parse($cartItem->end_date);

        $this->availabilityService->bookEquipment($equipment, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $orderId, 'booked');
    }

    protected function getNextCompanyOrderNumber(?int $lesseeCompanyId, ?int $lessorCompanyId): int
    {
        if (!is_null($lesseeCompanyId)) {
            $lastOrder = Order::where('lessee_company_id', $lesseeCompanyId)->orderBy('company_order_number', 'desc')->first();
        } elseif (!is_null($lessorCompanyId)) {
            $lastOrder = Order::where('lessor_company_id', $lessorCompanyId)->orderBy('company_order_number', 'desc')->first();
        } else {
            return 1;
        }
        return ($lastOrder->company_order_number ?? 0) + 1;
    }
}
