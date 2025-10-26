<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
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

    public function source()
    {
        return $this->morphTo();
    }

    public function transactionEntry()
    {
        return $this->belongsTo(TransactionEntry::class, 'source_id');
    }
}
