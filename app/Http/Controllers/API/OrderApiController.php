<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Equipment;
use App\Services\CartService;
use App\Services\AdminNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderApiController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_address' => 'nullable|string|max:500',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        if (!$user->company) {
            return response()->json(['error' => 'Компания не найдена'], 400);
        }

        $cart = Cart::where('user_id', $user->id)
            ->where('type', Cart::TYPE_REGULAR)
            ->with('items.equipment.rentalTerms', 'items.equipment.company')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Корзина пуста'], 400);
        }

        try {
            DB::beginTransaction();
            $order = Order::create([
                'user_id' => $user->id,
                'lessee_company_id' => $user->company_id,
                'status' => Order::STATUS_PENDING,
                'total_amount' => $cart->total_base_amount,
                'notes' => $validated['comment'] ?? null,
                'start_date' => $cart->items->min('start_date'),
                'end_date' => $cart->items->max('end_date'),
            ]);

            foreach ($cart->items as $item) {
                $equipment = $item->equipment;
                $isPlatform = $equipment && $equipment->isPlatformOwned();
                $pricePerUnit = (float)($item->base_price ?? 0);
                $platformFee = (float)($item->platform_fee ?? 0);
                $totalPrice = (float)($item->total_price ?? 0);
                $deliveryCost = (float)($item->delivery_cost ?? 0);
                $discountAmount = (float)($item->discount_amount ?? 0);

                OrderItem::create([
                    'order_id' => $order->id,
                    'equipment_id' => $item->equipment_id,
                    'rental_term_id' => $item->rental_term_id,
                    'quantity' => $item->quantity ?? 1,
                    'base_price' => $pricePerUnit,
                    'price_per_unit' => $pricePerUnit,
                    'platform_fee' => $platformFee,
                    'delivery_cost' => $deliveryCost,
                    'discount_amount' => $discountAmount,
                    'total_price' => $totalPrice,
                    'period_count' => $item->period_count,
                    'fixed_lessor_price' => $pricePerUnit - $platformFee,
                    'fixed_customer_price' => $pricePerUnit,
                    'lessor_company_id' => $isPlatform ? null : ($equipment->company_id ?? null),
                    'status' => OrderItem::STATUS_PENDING,
                    'distance_km' => $item->distance_km ?? 0,
                ]);
            }

            // Очистка корзины
            $cart->items()->delete();
            DB::commit();

            // Уведомление админу о новом заказе
            try {
                $lesseeName = $user->company->legal_name ?? $user->name;
                app(AdminNotificationService::class)->newOrder(
                    "#{$order->id}",
                    $lesseeName,
                    null,
                    $order->total_amount ?? 0
                );
            } catch (\Throwable $e) {
                Log::error('Ошибка уведомления о новом заказе', ['error' => $e->getMessage()]);
            }

            return response()->json(['success' => true, 'order_id' => $order->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation error: '.$e->getMessage());
            return response()->json(['error' => 'Ошибка создания заказа: '.$e->getMessage()], 500);
        }
    }

    public function storeFromProposal(Request $request)
    {
        $user = auth()->user();

        $cart = Cart::where('user_id', $user->id)
            ->where('type', Cart::TYPE_PROPOSAL)
            ->with('items.proposal.equipment.rentalTerms', 'items.proposal.lessor.company', 'items.proposal.rentalRequest')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Корзина заявок пуста'], 400);
        }

        try {
            DB::beginTransaction();

            // Создаём отдельный заказ для каждого предложения
            foreach ($cart->items as $item) {
                $proposal = $item->proposal;
                if (!$proposal) continue;

                $requestData = $proposal->rentalRequest;

                $order = Order::create([
                    'user_id' => $user->id,
                    'lessee_company_id' => $user->company_id,
                    'lessor_company_id' => $proposal->lessor->company_id,
                    'status' => Order::STATUS_PENDING_APPROVAL,
                    'total_amount' => $proposal->proposed_price,
                    'start_date' => $requestData->rental_period_start ?? now(),
                    'end_date' => $requestData->rental_period_end ?? now()->addDay(),
                    'request_response_id' => $proposal->id,
                ]);

                OrderItem::create([
                    'order_id' => $order->id,
                    'equipment_id' => $proposal->equipment_id,
                    'rental_term_id' => $proposal->equipment->rentalTerms->first()->id ?? null,
                    'quantity' => $proposal->proposed_quantity ?? 1,
                    'base_price' => $proposal->proposed_price,
                    'price_per_unit' => $proposal->proposed_price,
                    'total_price' => $proposal->proposed_price,
                    'status' => OrderItem::STATUS_PENDING,
                ]);

                $proposal->update(['status' => 'accepted']);
            }

            $cart->items()->delete();
            DB::commit();

            // Уведомление админу о новом заказе из предложения
            try {
                $lesseeName = $user->company->legal_name ?? $user->name;
                $lessorName = $proposal->lessor->company->legal_name ?? null;
                app(AdminNotificationService::class)->newOrder(
                    "#{$order->id}",
                    $lesseeName,
                    $lessorName,
                    $proposal->proposed_price ?? 0
                );
            } catch (\Throwable $e) {
                Log::error('Ошибка уведомления о заказе из предложения', ['error' => $e->getMessage()]);
            }

            return response()->json(['success' => true, 'message' => 'Заказы созданы']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Proposal order creation error: '.$e->getMessage());
            return response()->json(['error' => 'Ошибка: '.$e->getMessage()], 500);
        }
    }
}
