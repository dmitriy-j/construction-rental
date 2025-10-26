<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentAvailability extends Model
{
    const STATUS_BOOKED = 'booked';

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled'; // Исправлено на правильное написание

    const STATUS_AVAILABLE = 'available';

    const STATUS_DELIVERY = 'delivery';

    const STATUS_TEMP_RESERVE = 'temp_reserve';

    protected $table = 'equipment_availability';

    protected $fillable = [
        'equipment_id',
        'date',
        'status',
        'order_id',
        'expires_at',
    ];

    protected $casts = [
        'date' => 'date',
        'expires_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_BOOKED,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED, // Исправлено
            self::STATUS_AVAILABLE,
            self::STATUS_DELIVERY,
            self::STATUS_TEMP_RESERVE,
        ];
    }

    public static function statusText(string $status): string
    {
        return match ($status) {
            self::STATUS_BOOKED => 'Забронировано',
            self::STATUS_ACTIVE => 'Активно',
            self::STATUS_COMPLETED => 'Завершено',
            self::STATUS_CANCELLED => 'Отменено', // Исправлено
            self::STATUS_AVAILABLE => 'Доступно',
            self::STATUS_DELIVERY => 'В пути',
            self::STATUS_TEMP_RESERVE => 'Временное резервирование',
            default => $status,
        };
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_BOOKED => 'info',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_COMPLETED => 'secondary',
            self::STATUS_CANCELLED => 'danger', // Исправлено
            self::STATUS_AVAILABLE => 'primary',
            self::STATUS_DELIVERY => 'warning',
            self::STATUS_TEMP_RESERVE => 'warning',
            default => 'light',
        };
    }

    // Accessors
    public function getStatusTextAttribute(): string
    {
        return self::statusText($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::statusColor($this->status);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if (! in_array($model->status, self::statuses())) {
                throw new \Exception("Недопустимый статус: {$model->status}");
            }
        });
    }
}
