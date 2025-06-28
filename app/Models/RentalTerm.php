<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalTerm extends Model
{
    use HasFactory;

    /**
     * Поля, доступные для массового заполнения
     */
    protected $table = 'equipment_rental_terms';

    protected $fillable = [
        'equipment_id',
        'period',
        'price',
        'currency'
    ];

    /**
     * Отношение к оборудованию
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Отношение к элементам заказа
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Получить форматированную цену
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2, '.', ' ') . ' ' . $this->currency;
    }

    /**
     * Получить полное описание периода
     */
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
