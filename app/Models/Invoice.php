<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'company_id',
        'number',
        'issue_date',
        'due_date',
        'amount',
        'amount_paid',
        'platform_fee',
        'status',
        'file_path',
        'idempotency_key',
        'paid_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_VIEWED = 'viewed';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELED = 'canceled';

    /**
     * Заказ, для которого выписан счет
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Компания-плательщик
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Рассчитать оставшуюся сумму к оплате
     */
    protected function remainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount - $this->amount_paid
        );
    }

    /**
     * Проверить, оплачен ли счет полностью
     */
    protected function isFullyPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->remaining_amount <= 0
        );
    }

    /**
     * Проверить, просрочен ли счет
     */
    protected function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn () => ! $this->isFullyPaid && $this->due_date->isPast()
        );
    }

    /**
     * Обновить статус счета на основе оплат и дат
     */
    public function updateStatus(): void
    {
        $newStatus = $this->status;

        if ($this->is_fully_paid) {
            $newStatus = self::STATUS_PAID;
        } elseif ($this->is_overdue) {
            $newStatus = self::STATUS_OVERDUE;
        } elseif ($this->status === self::STATUS_DRAFT) {
            $newStatus = self::STATUS_SENT;
        }

        if ($newStatus !== $this->status) {
            $this->update(['status' => $newStatus]);
        }
    }
}
