<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Waybill extends Model
{
    use HasFactory;

    const STATUS_FUTURE = 'future';

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const SHIFT_DAY = 'day';

    const SHIFT_NIGHT = 'night';

    protected $fillable = [
        'number',
        'order_id',
        'order_item_id',
        'equipment_id',
        'operator_id',
        'license_plate',
        'start_date',
        'end_date',
        'status',
        'notes',
        'operator_notes',
        'foreman_signature_path',
        'supervisor_signature_path',
        'rental_condition_id',
        'hourly_rate',
        'shift_type',
        'lessor_hourly_rate',
        'perspective',
        'related_waybill_id',
        'parent_order_id',
        'upd_id',

    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function relatedWaybill()
    {
        return $this->belongsTo(Waybill::class, 'related_waybill_id');
    }

    public function upd()
    {
        return $this->belongsTo(Upd::class);
    }

    public function canUploadUpd(): bool
    {
        return $this->status === 'completed' && ! $this->upd_id;
    }

    public function scopeCanUploadUpd($query)
    {
        return $query->where('status', 'completed')
            ->whereNull('upd_id');
    }

    public function parentOrder()
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Генерация номера в зависимости от перспективы
            if ($model->perspective === 'lessor') {
                $model->number = 'ЭСМ-2-'.date('Ymd').'-'.str_pad(static::where('perspective', 'lessor')->count() + 1, 5, '0', STR_PAD_LEFT);
            } else {
                $model->number = 'ПЛ-АР-'.date('Ymd').'-'.str_pad(static::where('perspective', 'lessee')->count() + 1, 5, '0', STR_PAD_LEFT);
            }

            // 2. Проверка соответствия оператора
            if ($model->operator && $model->operator->shift_type !== $model->shift_type) {
                throw new \Exception("Оператор {$model->operator->full_name} не соответствует типу смены. Ожидается: {$model->shift_type}, фактически: {$model->operator->shift_type}");
            }
        });

        static::updating(function ($model) {
            // 3. Логирование изменений статуса
            if ($model->isDirty('status')) {
                WaybillStatusHistory::create([
                    'waybill_id' => $model->id,
                    'old_status' => $model->getOriginal('status'),
                    'new_status' => $model->status,
                    'changed_by' => auth()->id(),
                ]);
            }
        });

        static::updated(function ($model) {
            // 4. Автоматическое обновление статуса при наличии отработанных часов
            if ($model->status === self::STATUS_FUTURE) {
                $hasWorkedShifts = $model->shifts()->where('hours_worked', '>', 0)->exists();
                if ($hasWorkedShifts) {
                    $model->update(['status' => self::STATUS_ACTIVE]);
                }
            }
        });
    }

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

    public function shifts(): HasMany
    {
        return $this->hasMany(WaybillShift::class);
    }

    public function isFuture(): bool
    {
        return $this->status === self::STATUS_FUTURE;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function markAsActive(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function complete(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_FUTURE => 'Будущий',
            self::STATUS_ACTIVE => 'Активный',
            self::STATUS_COMPLETED => 'Завершен',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_FUTURE => 'warning',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_COMPLETED => 'secondary',
            default => 'light',
        };
    }

    public function getTotalHoursAttribute()
    {
        return $this->shifts->sum('hours_worked');
    }

    public function getTotalAmountAttribute()
    {
        return $this->shifts->sum('total_amount');
    }

    public function getFirstShiftDateAttribute()
    {
        return $this->shifts()->orderBy('shift_date')->first()->shift_date ?? null;
    }

    public function completionAct()
    {
        return $this->hasOne(CompletionAct::class);
    }

    public function completionActs()
    {
        return $this->hasMany(CompletionAct::class);
    }

    public function firstShift()
    {
        return $this->hasOne(WaybillShift::class)->oldestOfMany('shift_date');
    }

    public function getShiftTypeTextAttribute(): string
    {
        return $this->shift_type === 'day' ? 'Дневная' : 'Ночная';
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(WaybillStatusHistory::class);
    }

    public function getBaseHourlyRateAttribute()
    {
        // Приоритет 1: Фиксированная цена из позиции заказа
        if ($this->orderItem && $this->orderItem->fixed_lessor_price) {
            return $this->orderItem->fixed_lessor_price;
        }

        // Приоритет 2: Из заказа
        if ($this->order && $this->equipment_id) {
            $item = $this->order->items()
                ->where('equipment_id', $this->equipment_id)
                ->first();

            return $item->fixed_lessor_price ?? $this->hourly_rate;
        }

        // Приоритет 3: Значение из путевого листа
        return $this->hourly_rate;
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class)->withDefault();
    }

    public function getDisplayHourlyRateAttribute()
    {
        $user = auth()->user();

        if ($user->company->is_lessor) {
            return $this->lessor_hourly_rate; // Для арендодателя - чистая ставка
        }

        return $this->hourly_rate; // Для остальных - с наценкой
    }

    public function orderItemWithFallback()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id')
            ->withDefault(function ($item, $waybill) {
                return new OrderItem([
                    'start_date' => $waybill->start_date,
                    'end_date' => $waybill->end_date,
                ]);
            });
    }

    public function allShiftsFilled(): bool
    {
        return $this->shifts()->where(function ($query) {
            $query->whereNull('hours_worked')
                ->orWhere('hours_worked', '<=', 0);
        })->doesntExist();
    }

    public function forLessor()
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'period' => $this->start_date->format('d.m.Y').' - '.$this->end_date->format('d.m.Y'),
            'equipment' => $this->equipment->title,
            'operator' => $this->operator->full_name,
            'total_hours' => $this->total_hours,
            'hourly_rate' => $this->lessor_hourly_rate,
            'total_amount' => $this->total_hours * $this->lessor_hourly_rate,
            'status' => $this->status_text,
            'perspective' => 'lessor',
        ];
    }

    public function forLessee()
    {
        $orderItem = $this->orderItem;
        $customerHourlyRate = $orderItem->price_per_unit;

        return [
            'id' => $this->id,
            'number' => $this->number,
            'period' => $this->start_date->format('d.m.Y').' - '.$this->end_date->format('d.m.Y'),
            'equipment' => $this->equipment->title,
            'operator' => 'Оператор платформы', // Скрываем реальное имя оператора
            'total_hours' => $this->total_hours,
            'hourly_rate' => $customerHourlyRate,
            'total_amount' => $this->total_hours * $customerHourlyRate,
            'status' => $this->status_text,
            'perspective' => 'lessee',
            'lessor_name' => 'Платформа', // Заменяем на нейтральное название
            'order_id' => $this->order_id,
            'parent_order_id' => $this->parent_order_id,
            'created_at' => $this->created_at,
        ];
    }

    public function scopeForLessor($query)
    {
        return $query->where('perspective', 'lessor');
    }
}
