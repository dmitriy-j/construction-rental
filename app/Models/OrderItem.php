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
        'rental_condition_id', // Добавлено
        'quantity',
        'base_price',
        'price_per_unit',
        'platform_fee',
        'discount_amount',
        'total_price',
        'period_count'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class)->withDefault([
            'title' => 'Удаленное оборудование',
            'brand' => 'N/A',
            'model' => 'N/A'
        ]);
    }

    public function rentalTerm(): BelongsTo
    {
        return $this->belongsTo(EquipmentRentalTerm::class, 'rental_term_id');
    }

    public function rentalCondition(): BelongsTo
    {
        return $this->belongsTo(RentalCondition::class, 'rental_condition_id');
    }

    public function deliveryNote()
    {
        return $this->hasOne(DeliveryNote::class, 'order_item_id');
    }
}
