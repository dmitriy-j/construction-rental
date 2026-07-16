<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceAdjustment extends Model
{
    protected $fillable = [
        'company_id',
        'admin_id',
        'type',
        'amount',
        'comment',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Сумма со знаком (+ для credit, - для debit)
     */
    public function getSignedAmountAttribute(): float
    {
        return $this->type === 'credit' ? $this->amount : -$this->amount;
    }

    /**
     * Тип на русском
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'credit' ? 'Начисление' : 'Списание';
    }
}
