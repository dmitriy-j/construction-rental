<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'equipment_id',
        'rental_term_id',
        'quantity',
        'base_price', // Добавлено
        'price_per_unit',
        'platform_fee', // Добавлено
        'discount_amount', // Добавлено
        'total_price',
        'period_count' // Добавлено
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function rentalTerm(): BelongsTo
    {
        return $this->belongsTo(EquipmentRentalTerm::class, 'rental_term_id');
    }
}
