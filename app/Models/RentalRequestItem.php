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
        'hourly_rate',
        'quantity',
        'specifications',
        'individual_conditions',
        'calculated_price',
        'use_individual_conditions',
        'custom_specs_metadata'
    ];

    // ⚠️ ИСПРАВЛЕНИЕ: Добавлено приведение типов для спецификаций
    protected $casts = [
        'specifications' => 'array',
        'individual_conditions' => 'array',
        'custom_specs_metadata' => 'array',
        'hourly_rate' => 'decimal:2',
        'calculated_price' => 'decimal:2',
        'use_individual_conditions' => 'boolean'
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

    public function getFormattedSpecificationsAttribute(): array
    {
        if (empty($this->specifications)) {
            return [];
        }

        try {
            $formatted = [];
            $specsArray = $this->specifications;
            $metadata = $this->custom_specs_metadata ?? [];

            \Log::debug('Raw specifications for item ' . $this->id, [
                'specifications' => $specsArray,
                'metadata' => $metadata
            ]);

            // Полный словарь переводов для всех возможных параметров
            $russianTranslations = [
                // Общие параметры
                'engine_power' => 'Мощность двигателя',
                'operating_weight' => 'Рабочий вес',
                'max_speed' => 'Максимальная скорость',
                'fuel_tank_capacity' => 'Объем топливного бака',
                'transmission' => 'Трансмиссия',
                'drive_type' => 'Тип привода',

                // Экскаваторы
                'bucket_volume' => 'Объем ковша',
                'max_digging_depth' => 'Максимальная глубина копания',
                'max_reach' => 'Максимальный вылет стрелы',
                'bucket_width' => 'Ширина ковша',
                'arm_force' => 'Усилие на рукояти',
                'boom_force' => 'Усилие на стреле',

                // Бульдозеры
                'blade_width' => 'Ширина отвала',
                'blade_height' => 'Высота отвала',
                'blade_capacity' => 'Объем отвала',
                'max_cutting_depth' => 'Максимальная глубина резания',
                'max_lifting_height' => 'Максимальная высота подъема',

                // Самосвалы
                'load_capacity' => 'Грузоподъемность',
                'body_volume' => 'Объем кузова',
                'body_length' => 'Длина кузова',
                'body_width' => 'Ширина кузова',
                'body_height' => 'Высота кузова',
                'unloading_angle' => 'Угол разгрузки',
                'axle_configuration' => 'Колёсная формула',

                // Краны
                'lifting_capacity' => 'Грузоподъёмность',
                'boom_length' => 'Длина стрелы',
                'outreach' => 'Вылет стрелы',
                'rotation_angle' => 'Угол поворота',

                // Катки
                'roller_width' => 'Ширина вальца',
                'roller_diameter' => 'Диаметр вальца',
                'vibration_frequency' => 'Частота вибрации',
                'amplitude' => 'Амплитуда',
                'compaction_width' => 'Ширина уплотнения',

                // Бетонная техника
                'concrete_output' => 'Производительность по бетону',
                'max_pressure' => 'Максимальное давление',
                'pump_height' => 'Высота подачи',

                // English variants
                'Bucket volume' => 'Объем ковша',
                'Engine power' => 'Мощность двигателя',
                'Operating weight' => 'Рабочий вес',
                'Max digging depth' => 'Максимальная глубина копания',
                'Blade width' => 'Ширина отвала',
                'Blade height' => 'Высота отвала',
                'Load capacity' => 'Грузоподъемность',
                'Body volume' => 'Объем кузова',
                'Max speed' => 'Максимальная скорость',
                'Lifting capacity' => 'Грузоподъёмность',
                'Boom length' => 'Длина стрелы',
                'Fuel tank capacity' => 'Объем топливного бака'
            ];

            // Обрабатываем спецификации с учетом метаданных
            foreach ($specsArray as $key => $value) {
                if ($value !== null && $value !== '') {
                    // Используем название из метаданных или переводим ключ
                    $label = $metadata[$key]['name'] ?? $russianTranslations[$key] ?? $this->formatLabel($key);
                    $unit = $metadata[$key]['unit'] ?? $this->getSimpleUnit($key);

                    $formattedValue = $value;

                    // Форматируем значение если есть единица измерения
                    $displayValue = $unit ? $value . ' ' . $unit : $value;

                    $formatted[] = [
                        'key' => $key,
                        'value' => $formattedValue,
                        'label' => $label,
                        'unit' => $unit,
                        'display_value' => $displayValue,
                        'formatted' => $label . ': ' . $displayValue,
                        'is_custom' => str_starts_with($key, 'custom_'),
                        'data_type' => $metadata[$key]['dataType'] ?? 'string'
                    ];
                }
            }

            // Сортируем: сначала стандартные параметры, потом кастомные
            usort($formatted, function ($a, $b) {
                if ($a['is_custom'] === $b['is_custom']) {
                    return $a['label'] <=> $b['label'];
                }
                return $a['is_custom'] ? 1 : -1;
            });

            \Log::debug('Formatted specifications for item ' . $this->id, [
                'count' => count($formatted),
                'formatted' => $formatted
            ]);

            return $formatted;
        } catch (\Exception $e) {
            \Log::error('Error formatting specifications for item ' . $this->id . ': ' . $e->getMessage());
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
        return $this->belongsTo(Category::class, 'category_id');
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
