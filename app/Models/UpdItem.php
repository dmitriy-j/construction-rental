<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'upd_id',
        'name',
        'quantity',
        'unit',
        'price',
        'amount',
        'vat_rate',
        'vat_amount',

        // Новые поля для 1С
        '1c_guid',
        'nomenclature_code',
        'nomenclature_type',
        'accounting_account',
        'vat_account',
        'cost_item',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
    ];

    public function upd(): BelongsTo
    {
        return $this->belongsTo(Upd::class);
    }
}
