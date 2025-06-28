<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Equipment;
use App\Models\EquipmentAvailability;
use Carbon\CarbonPeriod;
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

    public function isAvailable(Equipment $equipment, $startDate, $endDate): bool
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

    /**
     * Бронирует оборудование на указанный период
     */
    public function bookEquipment(
        Equipment $equipment,
        $startDate,
        $endDate,
        ?int $orderId = null,
        string $status = 'booked'
    ): void {
        if (is_string($startDate)) $startDate = \Carbon\Carbon::parse($startDate);
        if (is_string($endDate)) $endDate = \Carbon\Carbon::parse($endDate);

        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            \App\Models\EquipmentAvailability::updateOrCreate(
                [
                    'equipment_id' => $equipment->id,
                    'date' => $date->format('Y-m-d')
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
}
