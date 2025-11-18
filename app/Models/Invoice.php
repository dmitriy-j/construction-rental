<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'company_id',
        'upd_id', // Добавляем связь с УПД
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
        'cancellation_reason', // Добавляем поле для причины отмены
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Константы статусов (используем существующие из миграции)
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
     * Генерация упрощенного русского номера счета
     */
    public static function generateSimpleInvoiceNumber(): string
    {
        $currentYear = date('Y');
        $lastInvoice = self::whereYear('created_at', $currentYear)
                          ->orderBy('id', 'desc')
                          ->first();

        $sequenceNumber = $lastInvoice ? (intval(substr($lastInvoice->number, -4)) + 1) : 1;

        return 'СЧ/' . $currentYear . '/' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Автоматическая генерация номера при создании
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->number)) {
                $invoice->number = self::generateSimpleInvoiceNumber();
            }
        });
    }

    /**
     * Компания-плательщик
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Связь с УПД (если счет связан с УПД)
     */
    public function upd(): BelongsTo
    {
        return $this->belongsTo(Upd::class);
    }

    /**
     * Позиции счета
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
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
            get: fn () => !$this->is_fully_paid && $this->due_date->isPast()
        );
    }

    /**
     * Получить прогресс оплаты в процентах
     */
    protected function paymentProgress(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount > 0 ? round(($this->amount_paid / $this->amount) * 100, 2) : 0
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
            $this->paid_at = $this->paid_at ?: now();
        } elseif ($this->is_overdue) {
            $newStatus = self::STATUS_OVERDUE;
        } elseif ($this->status === self::STATUS_DRAFT && $this->file_path) {
            $newStatus = self::STATUS_SENT;
        }

        if ($newStatus !== $this->status) {
            $this->update(['status' => $newStatus]);
        }
    }

    /**
     * Отменить счет
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_CANCELED,
            'cancellation_reason' => $reason
        ]);
    }

    /**
     * Добавить платеж
     */
    public function addPayment(float $amount): void
    {
        $this->update([
            'amount_paid' => $this->amount_paid + $amount
        ]);

        $this->updateStatus();
    }

    /**
     * Получить доступные статусы
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_SENT => 'Отправлен',
            self::STATUS_VIEWED => 'Просмотрен',
            self::STATUS_PAID => 'Оплачен',
            self::STATUS_OVERDUE => 'Просрочен',
            self::STATUS_CANCELED => 'Отменен',
        ];
    }
}
