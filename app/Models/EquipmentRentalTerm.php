<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; // Исправленный импорт
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
        'period',
        'price',
        'currency',
        'delivery_price',
        'delivery_days',
        'return_policy'
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2, '.', ' ') . ' ' . $this->currency;
    }

    public function getFullPeriodAttribute(): string
    {
        $periods = [
            'час' => 'в час',
            'смена' => 'за смену',
            'сутки' => 'в сутки',
            'месяц' => 'в месяц',
        ];

        return $periods[$this->period] ?? $this->period;
    }
}
