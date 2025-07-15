<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentAvailability;
use Carbon\Carbon;

class EquipmentAvailabilityService
{
    public function getStatusDetails(Equipment $equipment): array
    {
        // Проверка глобального статуса оборудования
        if ($equipment->global_status === 'maintenance') {
            return $this->formatStatus('maintenance', 'На обслуживании', 'secondary');
        }

        if ($equipment->global_status === 'out_of_service') {
            return $this->formatStatus('out_of_service', 'Не эксплуатируется', 'dark');
        }

        // Проверка доступности на сегодня
        $today = Carbon::today()->toDateString();
        $availability = EquipmentAvailability::where('equipment_id', $equipment->id)
            ->where('date', $today)
            ->first();

        // Если записи нет - считаем доступным
        if (!$availability) {
            return $this->formatStatus('available', 'Доступно', 'success');
        }

        // Обработка статуса с учетом возможности продления
        if ($availability->status === 'booked') {
            return $this->handleBookedStatus($equipment, $today);
        }

        // Обработка статусов из equipment_availability
        switch ($availability->status) {
            case 'booked':
                return $this->handleBookedStatus($equipment, $today);

            case 'maintenance':
                return $this->formatStatus('maintenance', 'На обслуживании', 'secondary');

            default: // available
                return $this->formatStatus('available', 'Доступно', 'success');
        }
    }

    private function handleBookedStatus(Equipment $equipment, string $today): array
    {
        $condition = $equipment->rentalCondition;
        $extensionPolicy = optional($condition)->extension_policy;

        // Если продление запрещено - показываем дату освобождения
        if ($extensionPolicy === 'not_allowed') {
            $nextAvailable = $this->calculateNextAvailableDate($equipment->id);
            $message = $nextAvailable
                ? 'Занята до '. $nextAvailable->format('d.m.Y')
                : 'Занята';

            return $this->formatStatus('unavailable', $message, 'warning');
        }

        // Для разрешенного продления показываем особый статус
        return $this->formatStatus('unavailable_extension', 'Недоступно (возможно продление)', 'danger');
    }

    private function formatStatus(string $status, string $message, string $class): array
    {
        return [
            'status' => $status,
            'message' => $message,
            'class' => $class
        ];
    }

    public function calculateNextAvailableDate(int $equipmentId): ?Carbon
    {
        $nextAvailable = EquipmentAvailability::where('equipment_id', $equipmentId)
            ->where('date', '>=', now())
            ->where(function($query) {
                $query->where('status', 'available')
                    ->orWhere(function($q) {
                        $q->where('status', 'temp_reserve')
                            ->where('expires_at', '<', now());
                    });
            })
            ->orderBy('date')
            ->first();

        return $nextAvailable?->date;
    }

    public function isAvailable(Equipment $equipment, $startDate, $endDate): bool
    {
        $conflicting = EquipmentAvailability::where('equipment_id', $equipment->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('status', 'booked')
                    ->orWhere('status', 'maintenance')
                    ->orWhere(function($q) {
                        $q->where('status', 'temp_reserve')
                            ->where('expires_at', '>', now());
                    });
            })
            ->exists();

        return !$conflicting;
    }

    public function cancelUserReserves(int $userId)
    {
        // Отменяем временные резервы пользователя
        EquipmentAvailability::where('user_id', $userId)
            ->where('status', 'temp_reserve')
            ->where('expires_at', '>', now())
            ->delete();

        Log::info("Cancelled temp reserves for user: $userId");
    }

     public function cancelTempReserves(int $userId)
    {
        $deleted = EquipmentAvailability::where('user_id', $userId)
            ->where('status', 'temp_reserve')
            ->where('expires_at', '>', now())
            ->delete();

        Log::info("Cancelled $deleted temp reserves for user: $userId");
        return $deleted;
    }
}
