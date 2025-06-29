<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Equipment;
use App\Models\EquipmentAvailability;
use Carbon\CarbonPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EquipmentAvailabilityService
{
    /**
     * Проверяет доступность оборудования в указанный период
     */
    public function checkAvailability(Equipment $equipment, string $startDate, string $endDate): bool
    {
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $exists = EquipmentAvailability::where('equipment_id', $equipment->id)
                ->where('date', $date->format('Y-m-d'))
                ->where('status', 'available')
                ->exists();

            if (!$exists) {
                return false;
            }
        }

        return true;
    }

    public function isAvailable(Equipment $equipment, $start, $end): bool
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        // Логирование параметров запроса
        \Log::info("Проверка доступности оборудования", [
            'equipment_id' => $equipment->id,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d')
        ]);

        $days = $start->diffInDays($end);
        for ($i = 0; $i <= $days; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');

            $status = EquipmentAvailability::where('equipment_id', $equipment->id)
                ->where('date', $date)
                ->value('status');

            // Если статус 'booked' или 'maintenance' - недоступно
            if (in_array($status, ['booked', 'maintenance'])) {
                \Log::warning("Оборудование недоступно", [
                    'equipment_id' => $equipment->id,
                    'date' => $date,
                    'status' => $status
                ]);
                return false;
            }
        }

        \Log::info("Оборудование доступно", ['equipment_id' => $equipment->id]);
        return true;
    }

    /**
     * Бронирует оборудование на указанный период
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
                    'status' => $status,
                    'order_id' => $orderId,
                    'updated_at' => now()
                ]
            );
        }
    }

    /**
     * Освобождает бронь оборудования по заказу
     */
    public function releaseBooking(Order $order): void
    {
        foreach ($order->items as $item) {
            EquipmentAvailability::where('equipment_id', $item->equipment_id)
                ->where('order_id', $order->id)
                ->update([
                    'status' => 'available',
                    'order_id' => null
                ]);
        }
    }

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
