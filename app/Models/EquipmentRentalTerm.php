<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; // Исправленный импорт
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

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

    public function calculatePeriodCount($start, $end): int
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        switch ($this->period) {
            case 'час':
                return $start->diffInHours($end);
            case 'смена':
                // Предположим, что смена = 8 часов
                return ceil($start->diffInHours($end) / 8);
            case 'сутки':
                return $start->diffInDays($end);
            case 'месяц':
                return $start->diffInMonths($end);
            default:
                return $start->diffInDays($end);
        }
    }
}
