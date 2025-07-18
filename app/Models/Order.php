<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Order extends Model
{
    use SoftDeletes, HasFactory;

    // Константы статусов
    const STATUS_PENDING = 'pending';
    const STATUS_PENDING_APPROVAL = 'pending_approval'; // Новый статус
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXTENSION_REQUESTED = 'extension_requested';
    const STATUS_REJECTED = 'rejected'; // Новый статус
    const STATUS_AGGREGATED = 'aggregated'; // Добавляем новый статус

    protected $fillable = [
        'lessee_company_id',
        'lessor_company_id',
        'user_id',
        'status',
        'total_amount',
        'notes',
        'start_date',
        'end_date',
        'service_start_date',
        'service_end_date',
        'contract_date',
        'extension_requested',
        'requested_end_date',
        'platform_id',
        'base_amount',
        'platform_fee',
        'discount_amount',
        'lessor_payout',
        'prepayment_amount',
        'penalty_amount',
        'contract_id',
        'rejection_reason', // Новое поле
        'confirmed_at', // Новое поле
        'rejected_at', // Новое поле
        'rental_condition_id', // Новое поле
        'shift_hours',
        'shifts_per_day',
        'payment_type',
        'transportation',
        'fuel_responsibility',
        'lessor_base_amount'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'service_start_date' => 'date',
        'service_end_date' => 'date',
        'requested_end_date' => 'datetime',
        'contract_date' => 'datetime',
        'extension_requested' => 'boolean',
        'prepayment_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_CONFIRMED,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_EXTENSION_REQUESTED,
            self::STATUS_REJECTED,
            // self::STATUS_AGGREGATED, // Добавляем новый
        ];
    }

    public static function statusText(string $status): string
    {
        return match($status) {
            self::STATUS_PENDING => 'Ожидает обработки',
            self::STATUS_PENDING_APPROVAL => 'Ожидает подтверждения арендодателем',
            self::STATUS_CONFIRMED => 'Подтвержден',
            self::STATUS_ACTIVE => 'Активен',
            self::STATUS_COMPLETED => 'Завершен',
            self::STATUS_CANCELLED => 'Отменен',
            self::STATUS_EXTENSION_REQUESTED => 'Запрос продления',
            self::STATUS_REJECTED => 'Отклонен',
           // self::STATUS_AGGREGATED => 'Агрегированный заказ',
            default => $status,
        };
    }

    public function lesseeCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'lessee_company_id');
    }

    public function lessorCompany()
    {
        return $this->belongsTo(Company::class, 'lessor_company_id')->withDefault([
            'legal_name' => 'Компания недоступна'
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function canGenerateCompletionAct(): bool
    {
        return in_array($this->status, ['active', 'completed'])
            && $this->service_start_date
            && $this->waybills()->exists()
            && !$this->completionAct;
    }

     public function deliveryNote(): HasOneThrough // Изменен тип возвращаемого значения
    {
        return $this->hasOneThrough(
            DeliveryNote::class,
            OrderItem::class,
            'order_id', // Внешний ключ в order_items
            'order_item_id', // Внешний ключ в delivery_notes
            'id', // Локальный ключ в orders
            'id' // Локальный ключ в order_items
        );
    }

    public function rentalCondition(): BelongsTo
    {
        return $this->belongsTo(RentalCondition::class);
    }

    public function waybills(): HasMany
    {
        return $this->hasMany(Waybill::class);
    }

    public function completionAct(): HasOne
    {
        return $this->hasOne(CompletionAct::class);
    }

    public function setServiceStartDate($date): void
    {
        $this->service_start_date = Carbon::parse($date);
        $this->save();
        $this->refresh();
    }

    public function cancel()
    {
        $allowedStatuses = [self::STATUS_PENDING, self::STATUS_PENDING_APPROVAL, self::STATUS_CONFIRMED];
        if (!in_array($this->status, $allowedStatuses)) {
            throw new \Exception('Невозможно отменить заказ в текущем статусе');
        }

        $this->update(['status' => self::STATUS_CANCELLED]);

        try {
            app(\App\Services\EquipmentAvailabilityService::class)->releaseBooking($this);
        } catch (\Exception $e) {
            Log::error('Ошибка снятия бронирования: '.$e->getMessage());
        }

        return $this;
    }

    public function getStatusColorAttribute(): string
    {
        // Для агрегированных заказов используем особый цвет
        if ($this->status === self::STATUS_AGGREGATED) {
            return 'info';
        }

        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_PENDING_APPROVAL => 'secondary',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_ACTIVE => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_EXTENSION_REQUESTED => 'secondary',
            self::STATUS_REJECTED => 'dark',
            default => 'light',
        };
    }

    public function getAggregatedStatusAttribute()
    {
        if ($this->isChild()) {
            return $this->status;
        }

        $statuses = $this->childOrders->pluck('status')->unique();

        if ($statuses->contains(Order::STATUS_CANCELLED)) {
            return 'partially_cancelled';
        }

        if ($statuses->contains(Order::STATUS_REJECTED)) {
            return 'partially_rejected';
        }

        if ($statuses->every(fn($s) => $s === Order::STATUS_COMPLETED)) {
            return 'completed';
        }

        if ($statuses->every(fn($s) => $s === Order::STATUS_ACTIVE)) {
            return 'active';
        }

        return 'processing';
    }

    public function getStatusTextAttribute(): string
    {
        return self::statusText($this->status);
    }

     public function getDeliveryCostAttribute(): ?float
    {
        // Исправленный метод
        if ($this->deliveryNote) {
            return $this->deliveryNote->calculated_cost;
        }

        // Альтернативный расчет через позиции заказа
        return $this->items->sum(function($item) {
            return $item->deliveryNote->calculated_cost ?? 0;
        });
    }

    public function belongsToLessee(User $user): bool
    {
        return $this->lessee_company_id === $user->company_id;
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total_amount, 2) . ' ₽';
    }

    public function parentOrder()
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    public function childOrders()
    {
        return $this->hasMany(Order::class, 'parent_order_id');
    }

    public function isParent()
    {
        return is_null($this->parent_order_id);
    }

    public function isChild()
    {
        return !is_null($this->parent_order_id);
    }

    public function getTotalItemsCountAttribute()
    {
        if ($this->isParent()) {
            return $this->childOrders->sum(function($childOrder) {
                return $childOrder->items->count();
            });
        }

        return $this->items->count();
    }

    public function getBaseAmountAttribute(): float
    {
        return $this->items->sum(function($item) {
            return $item->base_price * $item->quantity;
        });
    }

    public function getFormattedBaseAmountAttribute(): string
    {
        return number_format($this->base_amount, 2) . ' ₽';
    }

    public function getLessorPayoutAttribute()
    {
        return $this->lessor_base_amount
            + $this->delivery_cost
            - $this->discount_amount;
    }

    public function getLesseeTotalAttribute()
    {
        return $this->base_amount
            + $this->delivery_cost
            - $this->discount_amount;
    }

    public function getTotalPayoutAttribute()
    {
        return $this->lessor_base_amount + $this->delivery_cost;
    }

    public function canBeActivated(): bool
    {
        return $this->status === self::STATUS_CONFIRMED &&
            now()->gte($this->start_date);
    }

    public function activationAvailableDate(): string
    {
        return $this->start_date->format('d.m.Y');
    }

}
