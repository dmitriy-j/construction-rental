<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function calculatePeriodCount(
            $startDate,
            $endDate,
            RentalCondition $condition
        ): int {
            try {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);

                // Простейший расчет по дням для теста
                return $end->diffInDays($start);

            } catch (\Exception $e) {
                \Log::error('Error calculating period count: ' . $e->getMessage());
                return 1;
            }
        }

    protected function calculateDistance($startDate, $endDate): float
    {
        // Заглушка для расчета дистанции
        // В реальном приложении здесь должна быть интеграция с GPS-трекерами
        // или использование данных из заказа
        return rand(50, 500); // Случайное расстояние в км
    }

    protected function calculateVolume($startDate, $endDate): float
    {
        // Заглушка для расчета объема
        // В реальном приложении здесь должна быть логика расчета
        // на основе характеристик оборудования и времени работы
        return rand(10, 100); // Случайный объем в м³
    }
}
