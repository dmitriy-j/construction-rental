<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_statement_transaction_id',
        'payee_inn',
        'payee_name',
        'amount',
        'purpose',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(BankStatementTransaction::class, 'bank_statement_transaction_id');
    }
}
