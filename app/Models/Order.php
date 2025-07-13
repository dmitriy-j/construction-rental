<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class Order extends Model
{
    use SoftDeletes, HasFactory;

    // Константы статусов
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXTENSION_REQUESTED = 'extension_requested';

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
        'penalty_amount'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'service_start_date' => 'date',
        'service_end_date' => 'date',
        'requested_end_date' => 'datetime',
        'contract_date' => 'datetime',
        'extension_requested' => 'boolean',
        'prepayment_amount' => 'decimal:2'
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_EXTENSION_REQUESTED,
        ];
    }

    public static function statusText(string $status): string
    {
        return match($status) {
            self::STATUS_PENDING => 'Ожидает подтверждения',
            self::STATUS_CONFIRMED => 'Подтвержден',
            self::STATUS_ACTIVE => 'Активен',
            self::STATUS_COMPLETED => 'Завершен',
            self::STATUS_CANCELLED => 'Отменен',
            self::STATUS_EXTENSION_REQUESTED => 'Запрос продления',
            default => $status,
        };
    }

    public function lesseeCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'lessee_company_id');
    }

    public function lessorCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'lessor_company_id');
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

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    public function canGenerateCompletionAct(): bool
    {
        return in_array($this->status, ['active', 'completed'])
            && $this->service_start_date
            && $this->waybills()->exists()
            && !$this->completionAct;
    }

    public function deliveryNote(): HasOne
    {
        return $this->hasOne(DeliveryNote::class);
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
        $allowedStatuses = [self::STATUS_PENDING, self::STATUS_CONFIRMED];
        if (!in_array($this->status, $allowedStatuses)) {
            throw new \Exception('Невозможно отменить заказ в текущем статусе');
        }

        $this->update(['status' => self::STATUS_CANCELLED]);
        app(\App\Services\EquipmentAvailabilityService::class)->releaseBooking($this);
        return $this;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_ACTIVE => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_EXTENSION_REQUESTED => 'secondary',
            default => 'light',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return self::statusText($this->status);
    }

    public function getDeliveryCostAttribute(): ?float
    {
        return $this->deliveryNote?->calculated_cost;
    }
}
