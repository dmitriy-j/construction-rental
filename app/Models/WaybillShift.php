<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaybillShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'waybill_id',
        'shift_date',
        'operator_id',
        'object_address',
        'object_name',
        'departure_time',
        'return_time',
        'odometer_start',
        'odometer_end',
        'fuel_start',
        'fuel_end',
        'fuel_refilled_liters',
        'fuel_refilled_type',
        'hours_worked',
        'downtime_hours',
        'downtime_cause',
        'work_description',
        'hourly_rate',
        'total_amount',
        'operator_signature_path',
        'mechanic_signature_path',
        'dispatcher_signature_path',
        'foreman_signature_path',
        'work_start_time', // Добавлено
        'work_end_time',   // Добавлено
    ];

    protected $casts = [
        'shift_date' => 'date',
        'departure_time' => 'datetime:H:i',
        'return_time' => 'datetime:H:i',
        'fuel_start' => 'decimal:2',
        'fuel_end' => 'decimal:2',
        'fuel_refilled_liters' => 'decimal:2',
        'hours_worked' => 'decimal:2',
        'downtime_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function waybill(): BelongsTo
    {
        return $this->belongsTo(Waybill::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    // Автоматический расчет суммы при сохранении
    protected static function booted()
    {
        static::saving(function ($model) {
            // Если часы уже установлены вручную - не пересчитывать
            if ($model->hours_worked > 0) {
                return;
            }

            if ($model->work_start_time && $model->work_end_time) {
                // Обрезаем секунды если есть
                $start = substr($model->work_start_time, 0, 5);
                $end = substr($model->work_end_time, 0, 5);

                $start = Carbon::createFromFormat('H:i', $start, 'Europe/Moscow');
                $end = Carbon::createFromFormat('H:i', $end, 'Europe/Moscow');

                if ($end < $start) {
                    $end->addDay();
                }

                $model->hours_worked = round($end->diffInMinutes($start) / 60, 2);
            }
        });
    }

    public function getFuelConsumedAttribute(): float
    {
        return ($this->fuel_start + $this->fuel_refilled_liters) - $this->fuel_end;
    }

    public function getShiftTypeAttribute()
    {
        return $this->waybill->shift_type;
    }

    public function getShiftTypeTextAttribute(): string
    {
        return $this->waybill->shift_type_text;
    }

    public function calculateHoursWorked()
    {
        $start = strtotime($this->work_start_time);
        $end = strtotime($this->work_end_time);

        if ($end < $start) {
            $end += 86400; // Добавляем 24 часа
        }

        return ($end - $start) / 3600;
    }
}
