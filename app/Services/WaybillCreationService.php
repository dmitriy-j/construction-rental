<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Waybill;
use App\Models\WaybillShift;
use App\Models\RentalCondition;
use App\Models\Equipment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WaybillCreationService
{
    /**
     * Создает путевые листы для всего заказа
     */
    public function createForOrder(Order $order)
    {
        $rentalCondition = $order->rentalCondition;

        if (!$rentalCondition) {
            Log::error('Rental condition not found for order', ['order_id' => $order->id]);
            throw new \Exception('Условия аренды не найдены для заказа');
        }

        $maxDays = $rentalCondition->max_waybill_days ?? 10;
        $shiftsPerDay = $rentalCondition->shifts_per_day ?? 1;

        foreach ($order->items as $item) {
            try {
                $this->createForOrderItem($item, $rentalCondition, $maxDays, $shiftsPerDay);
            } catch (\Exception $e) {
                Log::error('Error creating waybills for order item', [
                    'order_item_id' => $item->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }

    /**
     * Создает путевые листы для позиции заказа
     */
    protected function createForOrderItem($item, RentalCondition $rentalCondition, int $maxDays, int $shiftsPerDay)
    {
        $equipment = $item->equipment;

        // Определяем тип смен ВНЕ цикла
        $shiftTypes = $shiftsPerDay == 2 ? ['day', 'night'] : ['day'];

        foreach ($shiftTypes as $shiftType) {
            $operatorId = $this->getOperatorId($equipment, $shiftType);

            if (!$operatorId) {
                // Добавляем лог вместо исключения
                Log::error("No active operator for shift", [
                    'equipment_id' => $equipment->id,
                    'shift_type' => $shiftType
                ]);
                continue; // Пропускаем смену, но не прерываем
            }

            $periods = $this->splitPeriod($item->start_date, $item->end_date, $maxDays);

            foreach ($periods as $index => $period) {
                $this->createWaybillForPeriod(
                    $item,
                    $period,
                    $shiftType,
                    $operatorId,
                    $rentalCondition->shift_hours,
                    $index
                );
            }
        }
    }

    /**
     * Получаем ID оператора для типа смены
     */
    protected function getOperatorId($equipment, $shiftType)
    {
        return $equipment->operators()
            ->where('shift_type', $shiftType)
            ->where('is_active', true)
            ->value('id');
    }
    /**
     * Создаем путевой лист для периода
     */
    protected function createWaybillForPeriod(
        $item,
        $period,
        $shiftType,
        $operatorId,
        $shiftHours,
        $index
    ) {
        $startDate = Carbon::parse($period['start']);
        $endDate = Carbon::parse($period['end']);

        // Статус: если период начинается сегодня или раньше - активный, иначе будущий
        $status = $startDate <= now()
            ? Waybill::STATUS_ACTIVE
            : Waybill::STATUS_FUTURE;

        // Если это первый путевой лист и статус FUTURE, но период уже начался - делаем активным
        if ($index === 0 && $status === Waybill::STATUS_FUTURE && $startDate <= now()) {
            $status = Waybill::STATUS_ACTIVE;
        }

        // Получаем ставки с приоритетом для фиксированных цен
        $customerRate = $item->fixed_customer_price ?? $item->rentalTerm->price_per_hour;
        $lessorRate = $item->fixed_lessor_price ?? $item->rentalTerm->price_per_hour;

        $waybill = Waybill::create([
            'order_id' => $item->order_id,
            'equipment_id' => $item->equipment_id,
            'operator_id' => $operatorId,
            'shift_type' => $shiftType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'hourly_rate' => $customerRate, // Ставка с наценкой
            'lessor_hourly_rate' => $lessorRate, // Чистая ставка
            'notes' => "Автоматически создан при активации заказа",
            'order_item_id' => $item->id // Ссылка на позицию заказа
        ]);

        // Передаем lessorRate как явный параметр
        $this->createShiftsForPeriod(
            $waybill,
            $startDate,
            $endDate,
            $shiftHours,
            $lessorRate // Важно: передаем значение явно
        );
    }

    /**
     * Создает смены для периода путевого листа
     */
    protected function createShiftsForPeriod(
        Waybill $waybill,
        Carbon $startDate,
        Carbon $endDate,
        int $shiftHours,
        float $lessorRate // Добавлен недостающий параметр
    ) {
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            WaybillShift::create([
                'waybill_id' => $waybill->id,
                'shift_date' => $currentDate->format('Y-m-d'),
                'operator_id' => $waybill->operator_id,
                'hourly_rate' => $lessorRate, // Используем переданное значение
                'fuel_consumption_standard' => $this->calculateFuelConsumption(
                    $waybill->equipment_id,
                    $shiftHours
                )
            ]);

            $currentDate->addDay();
        }
    }
    /**
     * Разбивает период на части по указанному количеству дней
     */
    protected function splitPeriod($startDate, $endDate, int $maxDays): array
    {

        // Добавьте проверку
        if ($startDate > $endDate) {
            throw new \Exception("Дата начала периода не может быть позже даты окончания");
        }

        $periods = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Рассчитываем общее количество дней аренды
        $totalDays = $start->diffInDays($end) + 1;

        // Если весь период <= 10 дней - один путевой лист
        if ($totalDays <= $maxDays) {
            return [['start' => $start, 'end' => $end]];
        }

        // Разбиваем на периоды по maxDays дней
        $currentStart = $start->copy();

        while ($currentStart <= $end) {
            $currentEnd = $currentStart->copy()->addDays($maxDays - 1);

            // Корректируем последний период
            if ($currentEnd > $end) {
                $currentEnd = $end;
            }

            $periods[] = [
                'start' => $currentStart->format('Y-m-d'),
                'end' => $currentEnd->format('Y-m-d'),
            ];

            // Переход к следующему периоду
            $currentStart = $currentEnd->copy()->addDay();
        }

        return $periods;
    }

    /**
     * Рассчитывает нормативный расход топлива
     */
    protected function calculateFuelConsumption(int $equipmentId, int $hours): float
    {
        $equipment = Equipment::find($equipmentId);
        return $equipment->fuel_consumption_rate * $hours;
    }
}
