<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Platform;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\EquipmentAvailabilityService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class CheckoutController extends Controller
{
    protected CartService $cartService;
    protected EquipmentAvailabilityService $availabilityService;
    protected PricingService $pricingService;

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
            'items.rentalTerm' // Добавляем загрузку rentalTerm
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

        return redirect()->route('orders.index') // Изменено с orders.show
            ->with('success', 'Заказы успешно оформлены! Создано заказов: ' . count($orders));
    }

    protected function createOrder($companyId, $items, Cart $cart, $startDate, $endDate)
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

    protected function createOrderItem(Order $order, $item)
    {
        OrderItem::create([
            'order_id' => $order->id,
            'equipment_id' => $item->rentalTerm->equipment_id,
            'rental_term_id' => $item->rental_term_id,
            'period_count' => $item->period_count,
            'base_price' => $item->base_price,
            'platform_fee' => $item->platform_fee,
            'price_per_unit' => $item->base_price,
            'total_price' => $item->total,
            'quantity' => 1,
            'discount_amount' => 0
        ]);
    }

    protected function bookEquipment($item, $startDate, $endDate, $orderId)
    {
        $equipment = $item->rentalTerm->equipment;
        $rentalTerm = $item->rentalTerm;

         // Получаем количество дней доставки из условия аренды
        $deliveryDays = $rentalTerm->delivery_days ?? 0; // Используем 0, если не указано

        // Учитываем срок доставки: начинаем бронь раньше на delivery_days дней
        $deliveryStartDate = $startDate->copy()->subDays($deliveryDays);

        $days = $deliveryStartDate->diffInDays($endDate) + 1;

        // Логирование параметров
        \Log::info("Попытка бронирования оборудования", [
            'equipment_id' => $equipment->id,
            'start_date' => $deliveryStartDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'order_id' => $orderId
        ]);

        if (!$this->availabilityService->isAvailable($equipment, $deliveryStartDate, $endDate)) {
            throw new Exception("Оборудование {$equipment->title} недоступно на выбранные даты с учетом срока доставки.");
        }

        $this->availabilityService->bookEquipment($equipment, $deliveryStartDate, $endDate, $orderId, 'booked');

        // Логирование успешного бронирования
        \Log::info("Оборудование успешно забронировано", [
            'equipment_id' => $equipment->id,
            'order_id' => $orderId
        ]);
    }
}
