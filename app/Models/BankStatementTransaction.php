<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankStatementTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_statement_id',
        'currency',
        'date',
        'amount',
        'type',
        'payer_name',
        'payer_inn',
        'payer_account',
        'payer_bic',
        'payee_name',
        'payee_inn',
        'payee_account',
        'payee_bic',
        'purpose',
        'idempotency_key',
        'company_id',
        'invoice_id',
        'status',
        'error_message',
        // Новые поля сопоставления
        'document_type',
        'document_id',
        'matched_company_id',
        'is_unmatched',
        'unmatched_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'is_unmatched' => 'boolean',
    ];

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Полиморфная связь с привязанным документом (Upd, Invoice, Order)
     */
    public function document(): MorphTo
    {
        return $this->morphTo('document', 'document_type', 'document_id');
    }

    /**
     * Найденный контрагент
     */
    public function matchedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'matched_company_id');
    }

    /**
     * Получить тип документа на русском
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'upd' => 'УПД',
            'invoice' => 'Счёт',
            'order' => 'Заказ',
            'contract' => 'Договор',
            default => 'Не определён',
        };
    }

    /**
     * Статус сопоставления на русском
     */
    public function getMatchingStatusLabelAttribute(): string
    {
        if ($this->document_type && $this->document_id) {
            return 'Сопоставлен';
        }
        if ($this->is_unmatched) {
            return 'Не сопоставлен';
        }
        return 'Ожидает';
    }

    /**
     * Получить сумму со знаком для отображения
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->type === 'credit' ? $this->amount : -$this->amount;
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function transactionEntry()
    {
        return $this->belongsTo(TransactionEntry::class, 'source_id');
    }
}
