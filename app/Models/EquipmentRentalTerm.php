<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentRentalTerm extends Model
{
    use HasFactory;

    protected $table = 'equipment_rental_terms';

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    protected $fillable = [
        'equipment_id',
        'price_per_hour',
        'price_per_km', // Новая поле
        'currency',
        'delivery_days',
        'return_policy',
        'min_rental_hours', // Минимальное время аренды
        'delivery_organized_by_lessor',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFormattedHourlyPriceAttribute(): string
    {
        return number_format($this->price_per_hour, 2, '.', ' ').' '.$this->currency.'/час';
    }

    public function getFormattedKmPriceAttribute(): string
    {
        return $this->price_per_km
            ? number_format($this->price_per_km, 2, '.', ' ').' '.$this->currency.'/км'
            : 'Не применяется';
    }

    public function calculateRentalCost(
        RentalCondition $condition,
        int $hours,
        float $distance = 0
    ): float {
        $cost = 0;

        // Основная стоимость аренды
        if ($condition->payment_type === 'mileage') {
            $cost = $distance * $this->price_per_km;
        } else {
            $cost = $hours * $this->price_per_hour;
        }

        // Минимальная стоимость аренды
        $minCost = $this->min_rental_hours * $this->price_per_hour;

        return max($cost, $minCost);
    }

    protected function calculateDistance($startDate, $endDate): float
    {
        // Реализация расчета дистанции
        return rand(50, 500);
    }
}
