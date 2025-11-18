<?php
// app/Services/OrderRecalculationService.php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RentalCondition;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderRecalculationService
{
    protected $pricingService;
    protected $availabilityService;

    public function __construct(
        PricingService $pricingService,
        EquipmentAvailabilityService $availabilityService
    ) {
        $this->pricingService = $pricingService;
        $this->availabilityService = $availabilityService;
    }

    /**
     * Основной метод пересчета заказа при изменении дат
     */
    public function recalculateOrderDates(Order $order, Carbon $newStartDate, Carbon $newEndDate): array
    {
        Log::info('Starting order recalculation', [
            'order_id' => $order->id,
            'old_start_date' => $order->start_date,
            'old_end_date' => $order->end_date,
            'new_start_date' => $newStartDate,
            'new_end_date' => $newEndDate
        ]);

        DB::beginTransaction();

        try {
            $results = [];

            if ($order->isParent()) {
                // Для родительского заказа пересчитываем всех детей
                foreach ($order->childOrders as $childOrder) {
                    $results[] = $this->recalculateChildOrder($childOrder, $newStartDate, $newEndDate);
                }
                $this->recalculateParentOrder($order);
            } else {
                // Для дочернего заказа пересчитываем напрямую
                $results[] = $this->recalculateChildOrder($order, $newStartDate, $newEndDate);
            }

            DB::commit();

            Log::info('Order recalculation completed successfully', [
                'order_id' => $order->id,
                'results' => $results
            ]);

            return [
                'success' => true,
                'message' => 'Заказ успешно пересчитан',
                'results' => $results
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order recalculation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка пересчета заказа: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Пересчет дочернего заказа
     */
    protected function recalculateChildOrder(Order $order, Carbon $newStartDate, Carbon $newEndDate): array
    {
        $originalAmount = $order->total_amount;

        // Обновляем даты заказа
        $order->start_date = $newStartDate;
        $order->end_date = $newEndDate;

        $recalculatedItems = [];

        foreach ($order->items as $item) {
            $recalculatedItems[] = $this->recalculateOrderItem($item, $newStartDate, $newEndDate);
        }

        // Пересчитываем итоговые суммы заказа
        $this->recalculateOrderTotals($order);

        $order->save();

        return [
            'order_id' => $order->id,
            'original_amount' => $originalAmount,
            'new_amount' => $order->total_amount,
            'difference' => $order->total_amount - $originalAmount,
            'recalculated_items' => $recalculatedItems
        ];
    }

    /**
     * Пересчет позиции заказа
     */
    protected function recalculateOrderItem(OrderItem $item, Carbon $newStartDate, Carbon $newEndDate): array
    {
        $rentalCondition = $item->rentalCondition;
        $rentalTerm = $item->rentalTerm;
        $equipment = $item->equipment;

        if (!$rentalCondition || !$rentalTerm || !$equipment) {
            throw new \Exception("Недостаточно данных для пересчета позиции #{$item->id}");
        }

        // Рассчитываем новые рабочие часы
        $workingHours = $this->calculateWorkingHours(
            $newStartDate,
            $newEndDate,
            $rentalCondition
        );

        // Получаем базовую цену арендодателя
        $lessorPricePerHour = $item->fixed_lessor_price ?? $rentalTerm->price_per_hour;

        // Пересчитываем через PricingService
        $priceCalculation = $this->pricingService->calculatePrice(
            $rentalTerm,
            $item->order->lesseeCompany,
            $workingHours,
            $rentalCondition
        );

        // Обновляем позицию
        $item->update([
            'period_count' => $workingHours,
            'base_price' => $priceCalculation['base_price_per_unit'],
            'price_per_unit' => $priceCalculation['base_price_per_unit'],
            'platform_fee' => $priceCalculation['platform_fee'],
            'total_price' => $priceCalculation['final_price'],
            'fixed_customer_price' => $priceCalculation['base_price_per_unit'],
            'fixed_lessor_price' => $lessorPricePerHour,
        ]);

        return [
            'item_id' => $item->id,
            'equipment' => $equipment->title,
            'working_hours' => $workingHours,
            'new_price' => $priceCalculation['final_price'],
            'platform_fee' => $priceCalculation['platform_fee']
        ];
    }

    /**
     * Расчет рабочих часов на основе дат и условий аренды
     */
    protected function calculateWorkingHours(Carbon $startDate, Carbon $endDate, RentalCondition $condition): int
    {
        $days = $startDate->diffInDays($endDate) + 1;
        $shiftHours = $condition->shift_hours ?? 8;
        $shiftsPerDay = $condition->shifts_per_day ?? 1;

        return $days * $shiftHours * $shiftsPerDay;
    }

    /**
     * Пересчет итоговых сумм заказа
     */
    protected function recalculateOrderTotals(Order $order): void
    {
        $order->load('items');

        $order->base_amount = $order->items->sum(function ($item) {
            return $item->base_price * $item->period_count;
        });

        $order->platform_fee = $order->items->sum('platform_fee');
        $order->delivery_cost = $order->items->sum('delivery_cost');
        $order->lessor_base_amount = $order->items->sum(function ($item) {
            return $item->fixed_lessor_price * $item->period_count;
        });

        $order->total_amount = $order->base_amount + $order->delivery_cost;
    }

    /**
     * Пересчет родительского заказа после изменения дочерних
     */
    protected function recalculateParentOrder(Order $parentOrder): void
    {
        $parentOrder->load('childOrders.items');

        $parentOrder->base_amount = $parentOrder->childOrders->sum('base_amount');
        $parentOrder->platform_fee = $parentOrder->childOrders->sum('platform_fee');
        $parentOrder->delivery_cost = $parentOrder->childOrders->sum('delivery_cost');
        $parentOrder->lessor_base_amount = $parentOrder->childOrders->sum('lessor_base_amount');
        $parentOrder->total_amount = $parentOrder->childOrders->sum('total_amount');

        // Обновляем даты родительского заказа
        $parentOrder->start_date = $parentOrder->childOrders->min('start_date');
        $parentOrder->end_date = $parentOrder->childOrders->max('end_date');

        $parentOrder->save();
    }

    /**
     * Проверка доступности оборудования на новые даты
     */
    public function checkAvailability(Order $order, Carbon $newStartDate, Carbon $newEndDate): array
    {
        $unavailableEquipment = [];

        $items = $order->isParent()
            ? $order->childOrders->flatMap->items
            : $order->items;

        foreach ($items as $item) {
            $isAvailable = $this->availabilityService->isAvailable(
                $item->equipment,
                $newStartDate,
                $newEndDate,
                $order->id // Исключаем текущий заказ из проверки
            );

            if (!$isAvailable) {
                $unavailableEquipment[] = [
                    'equipment' => $item->equipment->title,
                    'item_id' => $item->id
                ];
            }
        }

        return [
            'available' => empty($unavailableEquipment),
            'unavailable_equipment' => $unavailableEquipment
        ];
    }
}
