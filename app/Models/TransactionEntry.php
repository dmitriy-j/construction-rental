<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TransactionEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'amount',
        'type',
        'purpose',
        'balance_snapshot',
        'description',
        'source_type',
        'source_id',
        'idempotency_key',
        'is_canceled',
        'cancel_reason',
        'canceled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_snapshot' => 'decimal:2',
        'is_canceled' => 'boolean',
        'canceled_at' => 'datetime',
        'issue_date' => 'datetime',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public const PURPOSE_LESSEE_PAYMENT = 'lessee_payment';

    public const PURPOSE_LESSOR_PAYOUT = 'lessor_payout';

    public const PURPOSE_PLATFORM_FEE = 'platform_fee';

    public const PURPOSE_REFUND = 'refund';

    public const PURPOSE_CORRECTION = 'correction';

    public const PURPOSE_UPD_DEBT = 'upd_debt';

    /**
     * Компания, к чьему балансу относится проводка
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Полиморфная связь с источником проводки (Order, Invoice, Upd)
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope для неподтвержденных проводок
     */
    public function scopeActive($query)
    {
        return $query->where('is_canceled', false);
    }

    /**
     * Scope для поиска по назначению
     */
    public function scopePurpose($query, $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    /**
     * Отмена проводки
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'is_canceled' => true,
            'cancel_reason' => $reason,
            'canceled_at' => now(),
        ]);
    }
}
