<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'name',
        'description',
        'quantity',
        'unit',
        'price',
        'amount',
        'vat_rate',
        'vat_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
    ];

    /**
     * Счет, к которому относится позиция
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Автоматический расчет суммы перед сохранением
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            if (empty($item->amount) && $item->quantity && $item->price) {
                $item->amount = $item->quantity * $item->price;
            }

            if ($item->vat_rate > 0 && $item->amount > 0) {
                $item->vat_amount = $item->amount * ($item->vat_rate / 100);
            }
        });
    }
}
