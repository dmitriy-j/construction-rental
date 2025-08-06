<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Waybill extends Model
{
    use HasFactory;

    // Типы смен
    const SHIFT_DAY = 'day';
    const SHIFT_NIGHT = 'night';

    // Статусы путевого листа
    const STATUS_CREATED = 'created';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'order_id',
        'equipment_id',
        'operator_id',
        'work_date',
        'shift',
        'hours_worked',
        'downtime_hours',
        'downtime_cause',
        'operator_signature_path',
        'customer_signature_path',
        'notes',
        'odometer_start',
        'odometer_end',
        'fuel_start',
        'fuel_end',
        'fuel_consumption_standard',
        'fuel_consumption_actual',
        'mechanic_signature_path',
        'work_description',
        'status',
        'rental_condition_id',

    ];

    protected $casts = [
        'work_date' => 'date',
        'fuel_start' => 'decimal:2',
        'fuel_end' => 'decimal:2',
        'fuel_consumption_standard' => 'decimal:2',
        'fuel_consumption_actual' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function rentalCondition(): BelongsTo
    {
        return $this->belongsTo(RentalCondition::class);
    }

    // Новые методы для статусов
    public function isCreated(): bool
    {
        return $this->status === self::STATUS_CREATED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function markAsInProgress(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    public function complete(array $data): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'odometer_end' => $data['odometer_end'],
            'fuel_end' => $data['fuel_end'],
            'fuel_consumption_actual' => $this->fuel_start - $data['fuel_end'],
            'hours_worked' => $data['hours_worked'],
            'downtime_hours' => $data['downtime_hours'],
            'downtime_cause' => $data['downtime_cause'],
            'work_description' => $data['work_description'],
            'completed_at' => now()
        ]);
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            self::STATUS_CREATED => 'Создан',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_COMPLETED => 'Завершен',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_CREATED => 'warning',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_COMPLETED => 'success',
            default => 'secondary',
        };
    }
}
