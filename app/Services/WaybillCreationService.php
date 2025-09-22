<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Operator;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Waybill;
use App\Models\WaybillShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WaybillCreationService
{
    public function createForOrder(Order $order)
    {
        try {
            $rentalCondition = $order->rentalCondition;
            $shiftsPerDay = $rentalCondition->shifts_per_day ?? 1;

            foreach ($order->items as $item) {
                $this->createFirstWaybillForItem($item, $shiftsPerDay);
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Ошибка создания путевых листов для заказа #{$order->id}: ".$e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e,
            ]);
            throw $e;
        }
        foreach ($order->waybills as $waybill) {
            $order->lesseeCompany->notify(
                new \App\Notifications\NewDocumentAvailable($waybill, 'путевой лист')
            );
        }
    }

    protected function createFirstWaybillForItem(OrderItem $item, int $shiftsPerDay)
    {
        try {
            $equipment = $item->equipment;
            $shiftTypes = $shiftsPerDay == 2 ? ['day', 'night'] : ['day'];

            // Проверяем и преобразуем даты с использованием заказа как fallback
            $startDate = $this->ensureCarbon($item->start_date) ?? $this->ensureCarbon($item->order->start_date);
            $endDate = $this->ensureCarbon($item->end_date) ?? $this->ensureCarbon($item->order->end_date);

            // Если даты все равно отсутствуют, используем текущую дату как fallback
            if (! $startDate) {
                $startDate = now();
                Log::warning("Использована текущая дата как start_date для item #{$item->id}");
            }

            if (! $endDate) {
                $endDate = $startDate->copy()->addDay();
                Log::warning("Использована start_date + 1 день как end_date для item #{$item->id}");
            }

            foreach ($shiftTypes as $shiftType) {
                $operator = $this->getOperatorForShift($equipment, $shiftType);
                $this->createWaybill(
                    $item,
                    $startDate,
                    $this->calculateEndDate($startDate, $endDate),
                    $shiftType,
                    $operator
                );
            }

        } catch (\Exception $e) {
            Log::error("Ошибка создания путевого листа для item #{$item->id}: ".$e->getMessage(), [
                'item_data' => $item->toArray(),
                'shifts_per_day' => $shiftsPerDay,
            ]);
            throw new \Exception("Не удалось создать путевой лист для позиции #{$item->id}: ".$e->getMessage());
        }
    }

    protected function ensureCarbon($date): ?Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }

        if (is_string($date)) {
            try {
                return Carbon::parse($date);
            } catch (\Exception $e) {
                Log::warning("Ошибка преобразования строки в дату: '$date'", [
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date);
        }

        return null;
    }

    protected function getOperatorForShift(Equipment $equipment, string $shiftType): Operator
    {
        $operator = $equipment->operators()
            ->where('shift_type', $shiftType)
            ->where('is_active', true)
            ->first();

        if (! $operator) {
            $error = "Отсутствует активный оператор для смены: $shiftType";
            Log::error($error, ['equipment_id' => $equipment->id]);
            throw new \Exception($error);
        }

        return $operator;
    }

    protected function calculateEndDate(Carbon $startDate, Carbon $endDate): Carbon
    {
        $firstPeriodEnd = $startDate->copy()->addDays(9);

        return $firstPeriodEnd->min($endDate);
    }

    protected function createWaybill(
        OrderItem $item,
        Carbon $startDate,
        Carbon $endDate,
        string $shiftType,
        Operator $operator
    ): Waybill {
        // Дополнительная проверка периода
        if ($endDate < $startDate) {
            Log::warning('Корректировка дат: end_date < start_date', [
                'item_id' => $item->id,
                'original_start' => $startDate,
                'original_end' => $endDate,
            ]);

            // Автоматическая корректировка
            $endDate = $startDate->copy()->addDay();
        }

        // Валидация периода (после корректировки)
        if ($endDate < $startDate) {
            Log::error('Invalid date range for waybill after correction', [
                'item_id' => $item->id,
                'start' => $startDate,
                'end' => $endDate,
            ]);
            throw new \Exception('Дата окончания не может быть раньше даты начала даже после корректировки');
        }

        $status = $startDate <= now()
            ? Waybill::STATUS_ACTIVE
            : Waybill::STATUS_FUTURE;

        $waybill = Waybill::create([
            'order_id' => $item->order_id,
            'parent_order_id' => $item->order->parent_order_id, // Добавляем привязку к родительскому заказу
            'order_item_id' => $item->id,
            'equipment_id' => $item->equipment_id,
            'operator_id' => $operator->id,
            'shift_type' => $shiftType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'hourly_rate' => $item->rentalTerm->price_per_hour,
            'lessor_hourly_rate' => $item->fixed_lessor_price ?? $item->rentalTerm->lessor_price,
            'notes' => 'Автоматически создан при активации заказа',
            'perspective' => 'lessor', // По умолчанию создаем для арендодателя
        ]);

        $this->createShifts($waybill, $startDate, $endDate);

        return $waybill;
    }

    protected function createShifts(Waybill $waybill, Carbon $startDate, Carbon $endDate)
    {
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            WaybillShift::create([
                'waybill_id' => $waybill->id,
                'shift_date' => $currentDate->format('Y-m-d'),
                'operator_id' => $waybill->operator_id,
                'hourly_rate' => $waybill->lessor_hourly_rate,
            ]);

            $currentDate->addDay();
        }
    }

    public function createNextWaybill(Waybill $currentWaybill): ?Waybill
    {
        $nextStart = $currentWaybill->end_date->copy()->addDay();

        // Используем связь через orderItem для получения конечной даты аренды
        $orderItem = $currentWaybill->orderItem()->with('order')->first();

        if (! $orderItem || ! $orderItem->order) {
            Log::error('Order item or parent order missing', ['waybill_id' => $currentWaybill->id]);

            return null;
        }

        // Определяем реальную дату окончания аренды для этой позиции заказа
        $rentalEndDate = $this->ensureCarbon($orderItem->end_date ?? $orderItem->order->end_date);

        // Если следующая дата начала уже позже даты окончания аренды - новый путевой лист не нужен
        if ($nextStart >= $rentalEndDate) {
            Log::info('Rental period is over, no next waybill needed.', [
                'waybill_id' => $currentWaybill->id,
                'next_start' => $nextStart,
                'rental_end' => $rentalEndDate,
            ]);

            return null;
        }

        // Рассчитываем дату окончания для нового путевого листа: минимум из (+10 дней) и (даты окончания аренды)
        $proposedEndDate = $nextStart->copy()->addDays(9); // 10 дней включительно: 11.08 - 20.08 = 10 дней
        $nextEnd = $proposedEndDate->min($rentalEndDate);

        Log::debug('Creating next waybill', [
            'current_waybill_id' => $currentWaybill->id,
            'next_start' => $nextStart,
            'proposed_end' => $proposedEndDate,
            'rental_end' => $rentalEndDate,
            'final_end' => $nextEnd,
        ]);

        return Waybill::create([
            'order_id' => $currentWaybill->order_id,
            'order_item_id' => $currentWaybill->order_item_id,
            'equipment_id' => $currentWaybill->equipment_id,
            'operator_id' => $currentWaybill->operator_id,
            'shift_type' => $currentWaybill->shift_type,
            'start_date' => $nextStart,
            'end_date' => $nextEnd,
            'status' => Waybill::STATUS_FUTURE,
            'hourly_rate' => $currentWaybill->hourly_rate,
            'lessor_hourly_rate' => $currentWaybill->lessor_hourly_rate,
            'notes' => 'Автоматически создан при закрытии предыдущего путевого листа',
        ]);
    }

    public function createShiftsForWaybill(Waybill $waybill)
    {
        return $this->createShifts($waybill, $waybill->start_date, $waybill->end_date);
    }
}
