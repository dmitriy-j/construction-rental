<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'total_base_amount',
        'total_platform_fee',
        'discount_amount',
        'start_date', // Добавлено
        'end_date' // Добавлено
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Пересчитывает итоговые суммы корзины
     */
    public function recalculateTotals(): void
    {
        $this->total_base_amount = $this->items->sum(function ($item) {
            return $item->base_price * $item->period_count;
        });

        $this->total_platform_fee = $this->items->sum(function ($item) {
            return $item->platform_fee * $item->period_count;
        });

        // Здесь должна быть логика расчета скидки
        $this->discount_amount = 0;

        $this->save();
    }

    /**
     * Устанавливает даты аренды для корзины
     */

    protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
    ];

    public function setDates(Carbon $startDate, Carbon $endDate): void
    {
        $this->update([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
}
