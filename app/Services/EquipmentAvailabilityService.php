<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentAvailability;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EquipmentAvailabilityService
{

    const STATUS_BOOKED = 'booked';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_AVAILABLE = 'available';
    const STATUS_DELIVERY = 'delivery';

    public function getStatusDetails(Equipment $equipment): array
    {
        // Проверка глобального статуса оборудования
        if ($equipment->global_status === 'maintenance') {
            return $this->formatStatus('maintenance', 'На обслуживании', 'secondary');
        }

        // Проверка доступности на сегодня
        $today = now()->format('Y-m-d');
        $availability = EquipmentAvailability::where('equipment_id', $equipment->id)
            ->where('date', $today)
            ->first();

        // Если записи нет - считаем доступным
        if (!$availability) {
            return $this->formatStatus('available', 'Доступно', 'success');
        }

        // Обработка статуса с учетом возможности продления
        if ($availability->status === 'booked') {
            $condition = $equipment->rentalCondition;
            $extensionPolicy = optional($condition)->extension_policy;

            // Если продление запрещено - показываем как недоступно
            if ($extensionPolicy === 'not_allowed') {
                return $this->formatStatus('unavailable', 'Недоступно', 'warning');
            }

            // Для разрешенного продления показываем особый статус
            return $this->formatStatus('unavailable_extension', 'Недоступно (возможно продление)', 'danger');
        }

        // Обработка других статусов
        switch ($availability->status) {
            case 'maintenance':
                return $this->formatStatus('maintenance', 'На обслуживании', 'secondary');
              case 'delivery':
                // Оборудование в пути, но доступно для заказа
                return $this->formatStatus('available', 'Доступно (в пути к клиенту)', 'success');
            default:
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
            ->where('date', '>=', now()->format('Y-m-d'))
            ->where('status', 'available')
            ->orderBy('date')
            ->first();

        return $nextAvailable ? Carbon::parse($nextAvailable->date) : null;
    }

    public function isAvailable(Equipment $equipment, $startDate, $endDate): bool
    {
        // Преобразуем даты, если они строки
        if (is_string($startDate)) $startDate = Carbon::parse($startDate);
        if (is_string($endDate)) $endDate = Carbon::parse($endDate);

        Log::debug('[AVAILABILITY] Проверка доступности', [
            'equipment_id' => $equipment->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ]);

        $conflicting = EquipmentAvailability::where('equipment_id', $equipment->id)
            ->whereBetween('date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ])
            ->where(function($query) {
                $query->where('status', 'booked')
                    ->orWhere('status', 'maintenance')
                    ->orWhere(function($q) {
                        $q->where('status', 'temp_reserve')
                            ->where('expires_at', '>', now());
                    });
            })
            ->exists();

        Log::debug('[AVAILABILITY] Результат проверки', ['conflicting' => $conflicting]);
        return !$conflicting;
    }

    public function updateStatus(
        int $equipmentId,
        string $startDate,
        string $endDate,
        string $status,
        int $orderId
    ) {
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            EquipmentAvailability::updateOrCreate(
                [
                    'equipment_id' => $equipmentId,
                    'date' => $date->format('Y-m-d'),
                ],
                [
                    'status' => $status,
                    'order_id' => $orderId
                ]
            );
        }
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

    public function validateRentalConditions(Order $order): bool
    {
        $condition = $order->rentalCondition;

        // Если условие не задано
        if (!$condition) {
            return false;
        }

        // Проверяем минимальный срок аренды
        $rentalDays = $order->start_date->diffInDays($order->end_date);
        if ($condition->min_rental_days && $rentalDays < $condition->min_rental_days) {
            return false;
        }

        // Проверяем тип оплаты
        if ($condition->payment_type === 'prepayment' && $order->prepayment_amount <= 0) {
            return false;
        }

        // Проверяем наличие необходимой документации
        if ($condition->required_documents) {
            $userDocuments = $order->user->company->documents ?? [];
            $missingDocs = array_diff($condition->required_documents, $userDocuments);

            if (!empty($missingDocs)) {
                return false;
            }
        }

        return true;
    }

    Public function bookEquipment(Equipment $equipment, $startDate, $endDate, $orderId, $status)
    {
        // Проверяем существование оборудования
        if (!$equipment->exists) {
            Log::error("Попытка бронирования несуществующего оборудования", [
                'equipment_id' => $equipment->id,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            throw new \Exception("Оборудование не найдено");
        }

        // Преобразуем даты
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            EquipmentAvailability::updateOrCreate(
                [
                    'equipment_id' => $equipment->id,
                    'date' => $date->format('Y-m-d'),
                ],
                [
                    'status' => $status,
                    'order_id' => $orderId,
                    'expires_at' => $status === EquipmentAvailability::STATUS_DELIVERY
                        ? $end->endOfDay()
                        : null
                ]
            );
        }

        Log::info("Оборудование забронировано", [
            'equipment_id' => $equipment->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status
        ]);

          // Для статуса "В пути" устанавливаем expires_at
            $expiresAt = null;
            if ($status === self::STATUS_DELIVERY) {
                $expiresAt = Carbon::parse($endDate)->endOfDay();
            }

            foreach ($period as $date) {
                EquipmentAvailability::updateOrCreate(
                    ['equipment_id' => $equipment->id, 'date' => $date->format('Y-m-d')],
                    [
                        'status' => $status,
                        'order_id' => $orderId,
                        'expires_at' => $expiresAt
                    ]
                );
            }

    }

    public function updateEquipmentStatus(OrderItem $item, string $status)
    {
        $equipment = $item->equipment;
        $order = $item->order;

        $this->bookEquipment(
            $equipment,
            $order->start_date->format('Y-m-d'),
            $order->end_date->format('Y-m-d'),
            $order->id,
            $status
        );

        // Обновляем статус в самой позиции заказа
        $item->update(['status' => $status]);
    }

    public function clearExpiredReservations()
    {
        $deleted = EquipmentAvailability::where('status', 'temp_reserve')
            ->where('expires_at', '<', now())
            ->delete();

        Log::info("Удалено устаревших резервов: $deleted");
        return $deleted;
    }

    public function bookDelivery(Order $order)
    {
        foreach ($order->items as $item) {
            $deliveryDays = $item->rentalTerm->delivery_days ?? 0;
            $startDate = Carbon::parse($item->start_date);

            // Расчет даты начала доставки
            $deliveryStartDate = $startDate->copy()->subDays($deliveryDays);
            $deliveryEndDate = $startDate->copy()->subDay();

            $this->bookEquipment(
                $item->equipment,
                $deliveryStartDate->format('Y-m-d'),
                $deliveryEndDate->format('Y-m-d'),
                $order->id,
                EquipmentAvailability::STATUS_DELIVERY // Используем константу
            );
        }
    }

    public function releaseBooking(Order $order)
    {
        try {
            // Для родительских заказов обрабатываем все дочерние заказы
            if ($order->isParent()) {
                foreach ($order->childOrders as $childOrder) {
                    $this->releaseOrderEquipment($childOrder);
                }
            } else {
                $this->releaseOrderEquipment($order);
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при освобождении оборудования: ' . $e->getMessage());
            throw $e;
        }
    }

    private function releaseOrderEquipment(Order $order)
    {
        foreach ($order->items as $item) {
            DB::table('equipment_availability')
                ->where('equipment_id', $item->equipment_id)
                ->where('order_id', $order->id)
                ->update([
                    'status' => self::STATUS_AVAILABLE, // Возвращаем в доступные
                    'updated_at' => now()
                ]);
        }
    }
}
