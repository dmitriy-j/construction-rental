<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\EquipmentAvailabilityService;
use App\Services\PricingService;
use App\Services\TransportCalculatorService;
use App\Services\DeliveryCalculatorService;
use App\Models\Order;
use App\Models\Platform;
use App\Models\DeliveryNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        Log::info('[CHECKOUT] Начало оформления заказа', [
            'user_id' => auth()->id(),
            'selected_items' => $request->input('selected_items', ''),
            'all_request_data' => $request->all()
        ]);

        try {
            $cart = $this->cartService->getCart();
            $selectedItems = $request->input('selected_items', '');

            // Декодируем JSON-строку в массив
            $selectedItems = json_decode($selectedItems, true) ?? [];

            // Если массив пуст, используем всю корзину
            if (empty($selectedItems)) {
                Log::warning('[CHECKOUT] Параметр selected_items пуст, используем всю корзину');
                $selectedItems = $cart->items->pluck('id')->toArray();
            }

            Log::debug('[CHECKOUT] Получена корзина', [
                'cart_id' => $cart->id,
                'item_count' => $cart->items->count(),
                'selected_items_count' => count($selectedItems)
            ]);

            $cart->load([
                'items.rentalTerm.equipment.company',
                'items.rentalCondition',
                'items.deliveryFrom',
                'items.deliveryTo'
            ]);

            $cartItems = $cart->items->filter(fn($item) => in_array($item->id, $selectedItems));

            if ($cartItems->isEmpty()) {
                Log::warning('[CHECKOUT] Корзина пуста после фильтрации');
                return redirect()->back()->with('error', 'Корзина пуста');
            }

            $groupedItems = $cartItems->groupBy(function ($item) {
                return $item->rentalTerm->equipment->company_id;
            });

            Log::info('[CHECKOUT] Группировка по арендодателям', [
                'groups' => $groupedItems->keys()->toArray()
            ]);

            $orders = [];

            DB::beginTransaction();
            Log::info('[CHECKOUT] Начало транзакции');

            try {
                foreach ($groupedItems as $companyId => $items) {
                    Log::info('[CHECKOUT] Создание заказа для арендодателя', [
                        'company_id' => $companyId,
                        'item_count' => $items->count()
                    ]);

                    $order = $this->createOrder($companyId, $items);
                    $orders[] = $order;

                    Log::info('[CHECKOUT] Заказ создан', [
                        'order_id' => $order->id,
                        'status' => $order->status
                    ]);

                    foreach ($items as $item) {
                        Log::debug('[CHECKOUT] Создание позиции заказа', [
                            'cart_item_id' => $item->id,
                            'equipment_id' => $item->rentalTerm->equipment_id
                        ]);

                        $this->createOrderItem($order, $item);
                        $this->bookEquipment($item, $order->id);
                    }
                }

                Log::info('[CHECKOUT] Удаление элементов из корзины', [
                    'item_ids' => $selectedItems
                ]);

                $cart->items()->whereIn('id', $selectedItems)->delete();
                $this->cartService->recalculateTotals($cart);

                DB::commit();
                Log::info('[CHECKOUT] Транзакция успешно завершена');

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('[CHECKOUT] Ошибка в транзакции', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', 'Ошибка оформления заказа: ' . $e->getMessage());
            }

            return redirect()->route('lessee.orders.index')
                ->with('success', 'Заказы успешно оформлены! Создано заказов: ' . count($orders));

        } catch (Exception $e) {
            Log::error('[CHECKOUT] Критическая ошибка', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Произошла непредвиденная ошибка: ' . $e->getMessage());
        }
    }

    protected function createOrder($companyId, $items)
    {
        $baseAmount = $items->sum(function ($item) {
            return $item->base_price * $item->period_count;
        });

        $platformFee = $items->sum(function ($item) {
            return $item->platform_fee * $item->period_count;
        });

        $discountAmount = $this->pricingService->getDiscount(
            auth()->user()->company,
            $baseAmount + $platformFee
        );

        $totalAmount = $baseAmount + $platformFee - $discountAmount;

        $startDate = $items->min('start_date');
        $endDate = $items->max('end_date');

        return Order::create([
            'user_id' => auth()->id(),
            'lessee_company_id' => auth()->user()->company_id,
            'lessor_company_id' => $companyId,
            'base_amount' => $baseAmount,
            'platform_fee' => $platformFee,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'status' => Order::STATUS_PENDING_APPROVAL,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => Platform::getMain()->id,
            'rental_condition_id' => $items->first()->rental_condition_id
        ]);
    }

    protected function createOrderItem($order, $item)
    {
        $orderItem = \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'equipment_id' => $item->rentalTerm->equipment_id,
            'rental_term_id' => $item->rental_term_id,
            'period_count' => $item->period_count,
            'base_price' => $item->base_price,
            'platform_fee' => $item->platform_fee,
            'price_per_unit' => $item->base_price,
            'total_price' => $item->base_price * $item->period_count,
            'quantity' => 1,
            'discount_amount' => 0,
            'delivery_cost' => $item->delivery_cost
        ]);

        if ($item->delivery_cost > 0) {
            $deliveryDays = $item->rentalTerm->delivery_days ?? 1;
            $deliveryDate = Carbon::parse($item->start_date)->subDays($deliveryDays);

            $vehicleType = $this->calculateVehicleType($item);
            $distance = $this->calculateDistance($item);

            DeliveryNote::create([
                'order_id' => $order->id,
                'cart_item_id' => $item->id,
                'delivery_from_id' => $item->delivery_from_id,
                'delivery_to_id' => $item->delivery_to_id,
                'vehicle_type' => $vehicleType,
                'distance_km' => $distance,
                'calculated_cost' => $item->delivery_cost,
                'delivery_date' => $deliveryDate,
                'driver_name' => 'Не указано', // Добавлено значение по умолчанию
                'receiver_name' => 'Не указано', // Для других обязательных полей
                'equipment_condition' => 'Хорошее' // Значение по умолчанию
            ]);
        }

        return $orderItem;
    }

    protected function bookEquipment($item, $orderId)
    {
        $equipment = $item->rentalTerm->equipment;
        $rentalTerm = $item->rentalTerm;

        $startDate = Carbon::parse($item->start_date);
        $endDate = Carbon::parse($item->end_date);
        $deliveryDays = $rentalTerm->delivery_days ?? 0;
        $deliveryStartDate = $startDate->copy()->subDays($deliveryDays);

        Log::debug('[BOOKING] Параметры бронирования', [
            'equipment_id' => $equipment->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'delivery_days' => $deliveryDays,
            'delivery_start_date' => $deliveryStartDate->format('Y-m-d'),
            'now' => now()->format('Y-m-d H:i:s'),
            'item_id' => $item->id
        ]);

        // Проверка доступности
        $isAvailable = $this->availabilityService->isAvailable(
            $equipment,
            $deliveryStartDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        Log::debug('[BOOKING] Результат проверки доступности', [
            'available' => $isAvailable
        ]);

        if (!$isAvailable) {
            // Получаем ближайшую доступную дату
            $nextAvailable = $this->availabilityService->calculateNextAvailableDate($equipment->id);

            Log::warning('[BOOKING] Оборудование недоступно', [
                'next_available' => $nextAvailable ? $nextAvailable->format('Y-m-d') : null
            ]);

            throw new \Exception("Оборудование {$equipment->title} недоступно. " .
                ($nextAvailable ? "Ближайшая доступная дата: {$nextAvailable->format('d.m.Y')}" : ""));
        }

        // Бронируем оборудование
        $this->availabilityService->bookEquipment(
            $equipment,
            $deliveryStartDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $orderId,
            'booked'
        );

        Log::info('[BOOKING] Оборудование успешно забронировано');
    }

    protected function calculateVehicleType($item): string
    {
        try {
            return app(TransportCalculatorService::class)
                ->calculateRequiredTransport($item->rentalTerm->equipment);
        } catch (\Exception $e) {
            Log::error('Ошибка расчета типа транспорта', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            return 'truck_25t';
        }
    }

    protected function calculateDistance($item): float
    {
        try {
            return app(DeliveryCalculatorService::class)->calculateDistance(
                $item->deliveryFrom->latitude,
                $item->deliveryFrom->longitude,
                $item->deliveryTo->latitude,
                $item->deliveryTo->longitude
            );
        } catch (\Exception $e) {
            Log::error('Ошибка расчета расстояния', [
                'item_id' => $item->id,
                'error' => $e->getMessage()
            ]);
            return 50.0;
        }
    }
}
