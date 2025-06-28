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
        'price_per_unit',
        'total_price'
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
        return $this->belongsTo(RentalTerm::class);
    }
}
