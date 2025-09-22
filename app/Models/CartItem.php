<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'rental_term_id',
        'period_count',
        'base_price',
        'fixed_lessor_price',
        'fixed_customer_price',
        'platform_fee',
        'start_date',
        'end_date',
        'rental_condition_id',
        'delivery_from_id',
        'delivery_to_id',
        'delivery_cost',
        'distance_km', // Добавляем поле
        'delivery_cost_calculated', // Флаг успешного расчета

    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function rentalTerm(): BelongsTo
    {
        return $this->belongsTo(EquipmentRentalTerm::class, 'rental_term_id');
    }

    /**
     * Полная стоимость позиции
     */
    public function getTotalAttribute(): float
    {
        return ($this->base_price + $this->platform_fee) * $this->period_count;
    }

    public function rentalCondition()
    {
        return $this->belongsTo(RentalCondition::class);
    }

    public function deliveryFrom(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_from_id');
    }

    public function deliveryTo(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'delivery_to_id');
    }

    public function getTotalPriceAttribute(): float
    {
        // Используем фиксированную цену для арендатора, если она есть
        $rentalPrice = $this->fixed_customer_price ?? $this->base_price;

        return ($rentalPrice * $this->period_count) + $this->delivery_cost;
    }

    public function getDisplayPriceAttribute(): string
    {
        return number_format($this->base_price, 2).' ₽';
    }

    public function getTotalWithoutDeliveryAttribute(): float
    {
        $rentalPrice = $this->fixed_customer_price ?? $this->base_price;

        return $rentalPrice * $this->period_count;
    }

    public function getTotalWithDeliveryAttribute(): float
    {
        return ($this->base_price * $this->period_count) + $this->delivery_cost;
    }
}
