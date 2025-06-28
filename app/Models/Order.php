<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    const STATUS_EXTENSION_REQUESTED = 'extension_requested';
    use SoftDeletes;

    protected $fillable = [
        'lessee_company_id',
        'lessor_company_id',
        'user_id',
        'status',
        'total_amount',
        'notes',
        'start_date',
        'end_date',
        'extension_requested',
        'requested_end_date'
    ];

    protected $casts = [
        'start_date' => 'datetime:Y-m-d',
        'end_date' => 'datetime:Y-m-d',
        'requested_end_date' => 'datetime:Y-m-d',
        'extension_requested' => 'boolean'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

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

    public function cancel()
    {
        // Проверка прав доступа (если нужно)
    // if ($this->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
    //     abort(403);
    // }

        $allowedStatuses = [self::STATUS_PENDING, self::STATUS_CONFIRMED];
        if (!in_array($this->status, $allowedStatuses)) {
        throw new \Exception('Невозможно отменить заказ в текущем статусе');
    }

        $this->update(['status' => self::STATUS_CANCELLED]);
        app(\App\Services\EquipmentAvailabilityService::class)->releaseBooking($this);
        return $this;
    }
}
