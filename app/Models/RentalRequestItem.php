<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class RentalRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_request_id',
        'category_id',
        'quantity',
        'hourly_rate',
        'use_individual_conditions',
        'individual_conditions',
        'specifications',
        'standard_specifications', // ✅ НОВОЕ
        'custom_specifications', // ✅ НОВОЕ
        'custom_specs_metadata',
        'calculated_price'
    ];

    // ⚠️ ИСПРАВЛЕНИЕ: Добавлено приведение типов для спецификаций
     protected $casts = [
        'use_individual_conditions' => 'boolean',
        'individual_conditions' => 'array',
        'specifications' => 'array',
        'standard_specifications' => 'array', // ✅ НОВОЕ
        'custom_specifications' => 'array', // ✅ НОВОЕ
        'custom_specs_metadata' => 'array',
        'calculated_price' => 'decimal:2'
    ];

    protected $appends = ['formatted_specifications'];

    // ⚠️ ИСПРАВЛЕНИЕ: Улучшенный аксессор для спецификаций
    protected function specifications(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $specs = json_decode($value, true) ?? [];

                // Обрабатываем числовые значения в спецификациях
                if (isset($specs['values']) && is_array($specs['values'])) {
                    foreach ($specs['values'] as $key => &$val) {
                        if (is_numeric($val)) {
                            $val = (float) $val;
                        }
                    }
                }

                return $specs;
            },
            set: function ($value) {
                if (is_array($value)) {
                    // Обеспечиваем корректное сохранение числовых значений
                    if (isset($value['values']) && is_array($value['values'])) {
                        foreach ($value['values'] as $key => &$val) {
                            if ($val !== null && $val !== '') {
                                $val = is_numeric($val) ? (float) $val : $val;
                            }
                        }
                    }
                }
                return json_encode($value ?? []);
            }
        );
    }

    // Метод для преобразования старой структуры в новую
    public function getUnifiedSpecificationsAttribute()
    {
        // Если уже есть новая структура - используем её
        if (!empty($this->standard_specifications) || !empty($this->custom_specifications)) {
            return [
                'standard' => $this->standard_specifications ?? [],
                'custom' => $this->custom_specifications ?? []
            ];
        }

        // Конвертируем старую структуру в новую
        return $this->convertLegacySpecifications();
    }

    private function convertLegacySpecifications()
    {
        $standard = [];
        $custom = [];

        if (!empty($this->specifications)) {
            foreach ($this->specifications as $key => $value) {
                if (str_starts_with($key, 'custom_')) {
                    $metadata = $this->custom_specs_metadata[$key] ?? [];
                    $custom[$key] = [
                        'label' => $metadata['name'] ?? $key,
                        'value' => $value,
                        'unit' => $metadata['unit'] ?? '',
                        'dataType' => $metadata['dataType'] ?? 'string'
                    ];
                } else {
                    $standard[$key] = $value;
                }
            }
        }

        return [
            'standard' => $standard,
            'custom' => $custom
        ];
    }

     // Сеттер для унифицированных спецификаций
    public function setUnifiedSpecificationsAttribute($value)
    {
        $standard = $value['standard'] ?? [];
        $custom = $value['custom'] ?? [];

        $this->attributes['standard_specifications'] = json_encode($standard);
        $this->attributes['custom_specifications'] = json_encode($custom);

        // Также обновляем старую структуру для обратной совместимости
        $legacySpecs = array_merge($standard, []);
        foreach ($custom as $key => $customSpec) {
            $legacySpecs[$key] = $customSpec['value'];
        }
        $this->attributes['specifications'] = json_encode($legacySpecs);
    }


    public function getFormattedSpecificationsAttribute(): array
    {
        if (empty($this->specifications)) {
            return [];
        }

        try {
            // ✅ ИСПРАВЛЕНИЕ: Используем сервис с передачей метаданных
            $rentalRequestService = app(\App\Services\RentalRequestService::class);

            // Подготавливаем данные для сервиса форматирования
            $specsData = $this->specifications;
            $metadata = $this->custom_specs_metadata ?? [];

            \Log::debug('🔧 Форматирование спецификаций с метаданными', [
                'item_id' => $this->id,
                'specifications_type' => gettype($specsData),
                'metadata_count' => count($metadata),
                'metadata_sample' => array_slice($metadata, 0, 2)
            ]);

            // Если это старая структура, преобразуем в новую
            if (!isset($specsData['standard_specifications']) && !isset($specsData['custom_specifications'])) {
                $standardSpecs = [];
                $customSpecs = [];

                foreach ($specsData as $key => $value) {
                    if (str_starts_with($key, 'custom_')) {
                        $customSpecs[$key] = $value;
                    } else {
                        $standardSpecs[$key] = $value;
                    }
                }

                $specsData = [
                    'standard_specifications' => $standardSpecs,
                    'custom_specifications' => $customSpecs
                ];
            }

            // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Передаем метаданные в сервис форматирования
            $formatted = $rentalRequestService->formatSpecifications($specsData, $metadata);

            \Log::debug('✅ Item specs formatted with metadata', [
                'item_id' => $this->id,
                'formatted_count' => count($formatted),
                'custom_specs_count' => count(array_filter($formatted, fn($spec) => $spec['is_custom'])),
                'custom_specs_sample' => array_slice(array_filter($formatted, fn($spec) => $spec['is_custom']), 0, 2)
            ]);

            return $formatted;

        } catch (\Exception $e) {
            \Log::error('Error formatting specifications for item ' . $this->id . ': ' . $e->getMessage(), [
                'specifications' => $this->specifications,
                'metadata' => $this->custom_specs_metadata ?? []
            ]);
            return [];
        }
    }

    private function formatLabel($key): string
    {
        // Убираем префикс custom_ для красивого отображения
        $cleanKey = str_replace('custom_', '', $key);
        return ucfirst(str_replace('_', ' ', $cleanKey));
    }

    private function getSimpleUnit($key): string
    {
        $units = [
            'bucket_volume' => 'м³',
            'engine_power' => 'л.с.',
            'operating_weight' => 'т',
            'max_digging_depth' => 'м',
            'blade_width' => 'м',
            'blade_height' => 'м',
            'load_capacity' => 'т',
            'boom_length' => 'м',
            'max_lifting_height' => 'м',
            'max_reach' => 'м',
            'rotation_angle' => '°',
            'drum_width' => 'м',
            'vibration_frequency' => 'Гц',
            'concrete_output' => 'м³/ч',
            'max_pressure' => 'бар',
            'body_volume' => 'м³',
            'max_speed' => 'км/ч',
            'fuel_tank_capacity' => 'л'
        ];

        return $units[$key] ?? '';
    }

    // Остальные методы остаются без изменений...
    public function getDisplayConditionsAttribute(): array
    {
        try {
            if ($this->use_individual_conditions && !empty($this->individual_conditions)) {
                return is_array($this->individual_conditions) ? $this->individual_conditions : [];
            }

            return $this->rentalRequest->rental_conditions ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getConditionsTypeAttribute(): string
    {
        return ($this->use_individual_conditions && !empty($this->individual_conditions)) ? 'individual' : 'general';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function rentalRequest(): BelongsTo
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function getEffectiveConditionsAttribute()
    {
        if ($this->use_individual_conditions && !empty($this->individual_conditions)) {
            return array_merge($this->rentalRequest->getDefaultRentalConditions(), $this->individual_conditions);
        }

        return $this->rentalRequest->rental_conditions ?? $this->rentalRequest->getDefaultRentalConditions();
    }

    public function getEffectiveHourlyRateAttribute()
    {
        return $this->hourly_rate ?? $this->rentalRequest->hourly_rate;
    }

    public function calculateItemPrice(): float
    {
        try {
            $conditions = $this->effective_conditions;
            $hourlyRate = $this->effective_hourly_rate;

            \Log::debug('Calculating item price', [
                'item_id' => $this->id,
                'hourly_rate' => $hourlyRate,
                'quantity' => $this->quantity,
                'conditions' => $conditions
            ]);

            $start = Carbon::parse($this->rentalRequest->rental_period_start);
            $end = Carbon::parse($this->rentalRequest->rental_period_end);
            $totalDays = $start->diffInDays($end) + 1;

            $hoursPerShift = $conditions['hours_per_shift'] ?? 8;
            $shiftsPerDay = $conditions['shifts_per_day'] ?? 1;

            // Точный расчет по формуле
            $costPerShift = $hourlyRate * $hoursPerShift;
            $costPerDay = $costPerShift * $shiftsPerDay;
            $costPerPeriod = $costPerDay * $totalDays;
            $totalCost = $costPerPeriod * $this->quantity;

            \Log::debug('Final price calculation for item', [
                'item_id' => $this->id,
                'hourly_rate' => $hourlyRate,
                'quantity' => $this->quantity,
                'hours_per_shift' => $hoursPerShift,
                'shifts_per_day' => $shiftsPerDay,
                'total_days' => $totalDays,
                'total_cost' => $totalCost
            ]);

            return $totalCost;

        } catch (\Exception $e) {
            \Log::error('Error calculating item price: ' . $e->getMessage(), [
                'item_id' => $this->id,
                'rental_request_id' => $this->rental_request_id
            ]);
            return 0;
        }
    }
}
