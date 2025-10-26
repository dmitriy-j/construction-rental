<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_statement_id',
        'company_inn',
        'company_name',
        'amount',
        'type',
        'purpose',
        'transaction_data',
        'status',
        'notes',
    ];

    protected $casts = [
        'transaction_data' => 'json',
        'amount' => 'decimal:2',
    ];

    public function bankStatement()
    {
        return $this->belongsTo(BankStatement::class);
    }
}
