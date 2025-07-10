<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\EquipmentAvailabilityService;
use App\Services\PricingService;
use App\Models\Order;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

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
        // Получаем корзину с необходимыми отношениями
        $cart = $this->cartService->getCart();
        $cart->load([
            'items.rentalTerm.equipment.company',
            'items.rentalTerm.equipment.category',
            'items.rentalTerm'
        ]);

        if ($cart->items->isEmpty()) {
            return redirect()->back()->with('error', 'Корзина пуста');
        }

        // Проверяем, что даты аренды установлены
        if (!$cart->start_date || !$cart->end_date) {
            return redirect()->back()->with('error', 'Пожалуйста, укажите даты аренды');
        }

        $startDate = Carbon::parse($cart->start_date);
        $endDate = Carbon::parse($cart->end_date);

        // Группируем позиции по company_id (арендодателю)
        $groupedItems = $cart->items->groupBy(function ($item) {
            return $item->rentalTerm->equipment->company_id;
        });

        $orders = [];

        // Используем транзакцию, чтобы обеспечить целостность данных
        DB::beginTransaction();
        try {
            foreach ($groupedItems as $companyId => $items) {
                // Создаем заказ для этого арендодателя
                $order = $this->createOrder($companyId, $items, $cart, $startDate, $endDate);
                $orders[] = $order;

                // Создаем позиции заказа и бронируем оборудование
                foreach ($items as $item) {
                    $this->createOrderItem($order, $item);
                    $this->bookEquipment($item, $startDate, $endDate, $order->id);
                }
            }

            // Очищаем корзину
            $this->cartService->clearCart();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ошибка оформления заказа: ' . $e->getMessage());
        }

        return redirect()->route('lessee.orders.index')
            ->with('success', 'Заказы успешно оформлены! Создано заказов: ' . count($orders));
    }

    protected function createOrder($companyId, $items, $cart, $startDate, $endDate)
    {
        // Рассчитываем суммы для заказа
        $baseAmount = $items->sum(function ($item) {
            return $item->base_price * $item->period_count;
        });

        $platformFee = $items->sum(function ($item) {
            return $item->platform_fee * $item->period_count;
        });

        // Применяем скидку арендатора
        $discountAmount = $this->pricingService->getDiscount(
            auth()->user()->company,
            $baseAmount + $platformFee
        );

        $totalAmount = $baseAmount + $platformFee - $discountAmount;

        return Order::create([
            'user_id' => auth()->id(),
            'lessee_company_id' => auth()->user()->company_id,
            'lessor_company_id' => $companyId,
            'base_amount' => $baseAmount,
            'platform_fee' => $platformFee,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'status' => Order::STATUS_PENDING,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => Platform::getMain()->id,
        ]);
    }

    protected function createOrderItem($order, $item)
    {
        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'equipment_id' => $item->rentalTerm->equipment_id,
            'rental_term_id' => $item->rental_term_id,
            'period_count' => $item->period_count,
            'base_price' => $item->base_price,
            'platform_fee' => $item->platform_fee,
            'price_per_unit' => $item->base_price,
            'total_price' => $item->base_price * $item->period_count,
            'quantity' => 1,
            'discount_amount' => 0
        ]);
    }

    protected function bookEquipment($item, $orderId)
    {
        $equipment = $item->rentalTerm->equipment;
        $rentalTerm = $item->rentalTerm;

        // Используем даты из корзины
        $startDate = Carbon::parse($item->start_date);
        $endDate = Carbon::parse($item->end_date);

        // Получаем количество дней доставки из условия аренды
        $deliveryDays = $rentalTerm->delivery_days ?? 0;

        // Учитываем срок доставки
        $deliveryStartDate = $startDate->copy()->subDays($deliveryDays);

        if (!$this->availabilityService->isAvailable($equipment, $deliveryStartDate, $endDate)) {
            throw new Exception("Оборудование {$equipment->title} недоступно на выбранные даты с учетом срока доставки.");
        }

        $this->availabilityService->bookEquipment($equipment, $deliveryStartDate, $endDate, $orderId, 'booked');
    }
}
