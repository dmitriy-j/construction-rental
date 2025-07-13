<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Equipment;
use App\Models\EquipmentAvailability;
use App\Models\EquipmentStatusLog;
use Carbon\CarbonPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EquipmentAvailabilityService
{
    /**
     * Проверяет доступность оборудования в указанный период
     *
     * @param Equipment $equipment
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    public function checkAvailability(Equipment $equipment, string $startDate, string $endDate): bool
    {
        return $this->isAvailable($equipment, $startDate, $endDate);
    }

    /**
     * Проверяет доступность оборудования в указанный период с улучшенной логикой
     *
     * @param Equipment $equipment
     * @param mixed $start
     * @param mixed $end
     * @return bool
     */
    public function isAvailable(Equipment $equipment, $start, $end): bool
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        Log::debug("Checking availability for equipment: {$equipment->id}", [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ]);

        $days = $start->diffInDays($end);
        $available = true;
        $reasons = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');

            $record = EquipmentAvailability::where('equipment_id', $equipment->id)
                ->where('date', $date)
                ->first();

            // Если запись существует и не просрочена
            if ($record) {
                // Игнорируем просроченные временные резервы
                if ($record->status === 'temp_reserve' && $record->expires_at < now()) {
                    Log::debug("Ignoring expired temp_reserve", ['date' => $date]);
                    continue;
                }

                // Проверяем статусы, которые делают оборудование недоступным
                if (in_array($record->status, ['booked', 'maintenance', 'temp_reserve'])) {
                    Log::debug("Date blocked", [
                        'date' => $date,
                        'status' => $record->status,
                        'order_id' => $record->order_id
                    ]);
                    $available = false;
                    $reasons[] = "{$date}: {$record->status}";
                }
            }
        }

        if (!$available) {
            Log::warning("Equipment not available", [
                'equipment_id' => $equipment->id,
                'reasons' => $reasons
            ]);
        }

        return $available;
    }

    /**
     * Бронирует оборудование на указанный период
     *
     * @param Equipment $equipment
     * @param mixed $start
     * @param mixed $end
     * @param int|null $orderId
     * @param string $status
     * @return void
     */
    public function bookEquipment(Equipment $equipment, $start, $end, ?int $orderId = null, string $status = 'booked'): void
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        $days = $start->diffInDays($end);

        for ($i = 0; $i <= $days; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');

            EquipmentAvailability::updateOrCreate(
                [
                    'equipment_id' => $equipment->id,
                    'date' => $date
                ],
                [
                    'status' => 'temp_reserve',
                    'user_id' => $userId,
                    'expires_at' => $expiresAt, // Убедитесь, что это поле заполняется
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        Log::info("Equipment booked", [
            'equipment_id' => $equipment->id,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'status' => $status,
            'order_id' => $orderId
        ]);
    }

    /**
     * Создает временное резервирование оборудования
     *
     * @param Equipment $equipment
     * @param mixed $start
     * @param mixed $end
     * @param int $userId
     * @param int $minutes
     * @return void
     */
    public function temporaryReserve(Equipment $equipment, $start, $end, int $userId, int $minutes = 30): void
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        $days = $start->diffInDays($end);
        $expiresAt = now()->addMinutes($minutes);

        for ($i = 0; $i <= $days; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');

            EquipmentAvailability::updateOrCreate(
                [
                    'equipment_id' => $equipment->id,
                    'date' => $date
                ],
                [
                    'status' => 'temp_reserve',
                    'user_id' => $userId,
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        Log::info("Temporary reserve created", [
            'equipment_id' => $equipment->id,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'user_id' => $userId,
            'expires_at' => $expiresAt
        ]);
    }

    /**
     * Освобождает бронь оборудования по заказу
     *
     * @param Order $order
     * @return void
     */
    public function releaseBooking(Order $order): void
    {
        foreach ($order->items as $item) {
            EquipmentAvailability::where('equipment_id', $item->equipment_id)
                ->where('order_id', $order->id)
                ->update([
                    'status' => 'available',
                    'order_id' => null,
                    'updated_at' => now()
                ]);
        }

        Log::info("Booking released for order", ['order_id' => $order->id]);
    }

    /**
     * Очищает просроченные временные резервы
     *
     * @return void
     */
    public function clearExpiredReserves(): void
    {
        $deleted = EquipmentAvailability::where('status', 'temp_reserve')
            ->where('expires_at', '<', now())
            ->delete();

        Log::debug("Expired reserves cleared", ['count' => $deleted]);
    }

    /**
     * Отменяет временное резервирование для пользователя
     *
     * @param int $userId
     * @return void
     */
    public function cancelUserReserves(int $userId): void
    {
        $deleted = EquipmentAvailability::where('user_id', $userId)
            ->where('status', 'temp_reserve')
            ->delete();

        Log::info("User temp reserves canceled", ['user_id' => $userId, 'count' => $deleted]);
    }

    /**
     * Фиксирует простой оборудования
     *
     * @param Equipment $equipment
     * @param string $startTime
     * @param string $endTime
     * @param string $status
     * @param bool $customerResponsible
     * @param float $penaltyAmount
     * @param string|null $notes
     * @param int|null $orderId
     * @return EquipmentStatusLog
     */
    public function reportDowntime(
        Equipment $equipment,
        string $startTime,
        string $endTime,
        string $status,
        bool $customerResponsible = false,
        float $penaltyAmount = 0,
        ?string $notes = null,
        ?int $orderId = null
    ): EquipmentStatusLog {
        $log = EquipmentStatusLog::create([
            'equipment_id' => $equipment->id,
            'order_id' => $orderId,
            'status' => $status,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'customer_responsible' => $customerResponsible,
            'penalty_amount' => $penaltyAmount,
            'notes' => $notes
        ]);

        // Если простой связан с заказом и клиент ответственен
        if ($customerResponsible && $orderId) {
            $order = Order::find($orderId);
            $order->update([
                'total_amount' => $order->total_amount + $penaltyAmount,
                'penalty_amount' => $order->penalty_amount + $penaltyAmount
            ]);
        }

        return $log;
    }
}
