<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalRequestRequest extends FormRequest
{
     public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:50',
            'hourly_rate' => 'required|numeric|min:0',
            'rental_period_start' => 'required|date|after:today',
            'rental_period_end' => 'required|date|after:rental_period_start',
            'location_id' => 'required|exists:locations,id',
            'delivery_required' => 'sometimes',

            // Позиции заявки
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:equipment_categories,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
            'items.*.hourly_rate' => 'sometimes|numeric|min:0',

            // ✅ УЛУЧШЕННАЯ ВАЛИДАЦИЯ: Разрешаем nullable для unit
            'items.*.standard_specifications' => 'sometimes|array',
            'items.*.standard_specifications.*' => 'nullable',

            'items.*.custom_specifications' => 'sometimes|array',
            'items.*.custom_specifications.*' => 'sometimes|array',
            'items.*.custom_specifications.*.label' => 'sometimes|string|max:255',
            'items.*.custom_specifications.*.value' => 'sometimes',
            'items.*.custom_specifications.*.unit' => 'nullable|string|max:50', // ✅ ИЗМЕНЕНИЕ: nullable вместо sometimes
            'items.*.custom_specifications.*.dataType' => 'sometimes|in:string,number',

            // Для обратной совместимости
            'items.*.specifications' => 'sometimes|array',
            'items.*.specifications.*' => 'nullable',
            'items.*.custom_specs_metadata' => 'sometimes|array',

            'items.*.individual_conditions' => 'sometimes|array',
            'items.*.use_individual_conditions' => 'sometimes|boolean',

            // Условия аренды
            'rental_conditions' => 'sometimes|array',
            'rental_conditions.payment_type' => 'sometimes|in:hourly,shift,daily',
            'rental_conditions.hours_per_shift' => 'sometimes|integer|min:1|max:24',
            'rental_conditions.shifts_per_day' => 'sometimes|integer|min:1|max:3',
            'rental_conditions.transportation_organized_by' => 'sometimes|in:lessor,lessee',
            'rental_conditions.gsm_payment' => 'sometimes|in:included,separate',
            'rental_conditions.accommodation_payment' => 'sometimes|boolean',
            'rental_conditions.extension_possibility' => 'sometimes|boolean',
            'rental_conditions.operator_included' => 'sometimes|boolean',
        ];
    }

     public function prepareForValidation()
    {
        \Log::debug('🔄 prepareForValidation with IMPROVED structure', [
            'has_items' => !empty($this->items),
            'items_count' => count($this->items ?? [])
        ]);

        // Преобразуем все чекбоксы в boolean
        $deliveryRequired = $this->has('delivery_required') &&
                        in_array($this->input('delivery_required'), ['true', '1', 'on'], true);

        // Обрабатываем чекбоксы в rental_conditions
        $rentalConditions = $this->input('rental_conditions', []);
        $checkboxes = ['operator_included', 'accommodation_payment', 'extension_possibility'];

        foreach ($checkboxes as $checkbox) {
            if (isset($rentalConditions[$checkbox])) {
                $rentalConditions[$checkbox] = in_array($rentalConditions[$checkbox], ['true', '1', 'on'], true);
            }
        }

        // ✅ УЛУЧШЕННАЯ ОБРАБОТКА СПЕЦИФИКАЦИЙ С ЗАЩИТОЙ ОТ NULL
        $items = $this->input('items', []);

        foreach ($items as &$item) {
            \Log::debug('🔧 IMPROVED Processing item with specs', [
                'category_id' => $item['category_id'] ?? 'unknown',
                'has_standard_specs' => !empty($item['standard_specifications']),
                'has_custom_specs' => !empty($item['custom_specifications']),
                'standard_specs_keys' => array_keys($item['standard_specifications'] ?? []),
                'custom_specs_keys' => array_keys($item['custom_specifications'] ?? [])
            ]);

            // 🔄 КОНВЕРТАЦИЯ ИЗ СТАРОЙ СТРУКТУРЫ В НОВУЮ (для обратной совместимости)
            if (empty($item['standard_specifications']) && empty($item['custom_specifications']) && !empty($item['specifications'])) {
                \Log::debug('🔄 Converting legacy specifications to new structure');
                $this->convertLegacySpecifications($item);
            }

            // ✅ УЛУЧШЕННАЯ ОБРАБОТКА СТАНДАРТНЫХ СПЕЦИФИКАЦИЙ
            $item['standard_specifications'] = $this->processStandardSpecifications(
                $item['standard_specifications'] ?? []
            );

            // ✅ УЛУЧШЕННАЯ ОБРАБОТКА КАСТОМНЫХ СПЕЦИФИКАЦИЙ С ЗАЩИТОЙ ОТ NULL
            $item['custom_specifications'] = $this->processCustomSpecifications(
                $item['custom_specifications'] ?? []
            );

            // 🔄 ОБНОВЛЯЕМ МЕТАДАННЫЕ ДЛЯ ОБРАТНОЙ СОВМЕСТИМОСТИ
            $customMetadata = [];
            foreach ($item['custom_specifications'] as $key => $customSpec) {
                // ✅ ИСПРАВЛЕНИЕ: Проверяем структуру кастомной спецификации
                if (is_array($customSpec) && isset($customSpec['label'])) {
                    $customMetadata[$key] = [
                        'name' => $customSpec['label'],
                        'dataType' => $customSpec['dataType'] ?? 'string',
                        'unit' => $customSpec['unit'] ?? ''
                    ];
                }
            }
            $item['custom_specs_metadata'] = $customMetadata;

            // 🔄 ОБНОВЛЯЕМ СТАРУЮ СТРУКТУРУ ДЛЯ ОБРАТНОЙ СОВМЕСТИМОСТИ
            $legacySpecs = array_merge(
                $item['standard_specifications'] ?? [],
                $this->extractCustomSpecValues($item['custom_specifications'] ?? [])
            );
            $item['specifications'] = $legacySpecs;

            // Обрабатываем hourly_rate
            if (isset($item['hourly_rate'])) {
                $item['hourly_rate'] = (float) str_replace(',', '.', $item['hourly_rate']);
            }

            // Обрабатываем use_individual_conditions как boolean
            if (isset($item['use_individual_conditions'])) {
                $item['use_individual_conditions'] = in_array($item['use_individual_conditions'], ['true', '1', 'on', true], true);
            }

            \Log::debug('✅ IMPROVED Item processed', [
                'final_standard_specs_count' => count($item['standard_specifications'] ?? []),
                'final_custom_specs_count' => count($item['custom_specifications'] ?? []),
                'final_legacy_specs_count' => count($item['specifications'] ?? [])
            ]);
        }

        $this->merge([
            'hourly_rate' => (float) str_replace(',', '.', $this->hourly_rate),
            'delivery_required' => $deliveryRequired,
            'rental_conditions' => $rentalConditions,
            'items' => $items,
        ]);

        \Log::debug('✅ IMPROVED prepareForValidation completed', [
            'final_items_count' => count($items),
            'first_item_standard_specs' => array_keys($items[0]['standard_specifications'] ?? []),
            'first_item_custom_specs' => array_keys($items[0]['custom_specifications'] ?? []),
            'first_item_legacy_specs' => array_keys($items[0]['specifications'] ?? [])
        ]);
    }

    // 🔥 ДОБАВЛЕННЫЙ МЕТОД: Обработка стандартных спецификаций
    private function processStandardSpecifications(array $specs): array
    {
        $processed = [];

        foreach ($specs as $key => $value) {
            // Пропускаем пустые значения
            if ($value === null || $value === '') {
                continue;
            }

            // Обрабатываем числовые значения
            if (is_numeric($value)) {
                $processed[$key] = (float) $value;
            } else {
                $processed[$key] = $value;
            }
        }

        \Log::debug('✅ Standard specifications processed', [
            'original_count' => count($specs),
            'processed_count' => count($processed),
            'processed_keys' => array_keys($processed)
        ]);

        return $processed;
    }

    // ✅ МЕТОД: Обработка кастомных спецификаций с защитой от null
    private function processCustomSpecifications(array $specs): array
    {
        $processed = [];

        foreach ($specs as $key => $spec) {
            // ✅ ИСПРАВЛЕНИЕ: Проверяем что это валидная кастомная спецификация
            if (!is_array($spec)) {
                \Log::warning("Invalid custom specification format", ['key' => $key, 'spec' => $spec]);
                continue;
            }

            // Проверяем обязательные поля
            if (!isset($spec['label']) || empty(trim($spec['label']))) {
                \Log::warning("Custom specification missing label", ['key' => $key, 'spec' => $spec]);
                continue;
            }

            $value = $spec['value'] ?? '';

            // Пропускаем пустые значения
            if ($value === '' || $value === null) {
                continue;
            }

            // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Гарантируем что unit всегда строка, не null
            $unitValue = '';
            if (isset($spec['unit']) && $spec['unit'] !== null) {
                $unitValue = (string) $spec['unit'];
            }

            $processedSpec = [
                'label' => trim($spec['label']),
                'value' => $value,
                'unit' => $unitValue, // ✅ Всегда строка
                'dataType' => $spec['dataType'] ?? 'string'
            ];

            // ✅ ИСПРАВЛЕНИЕ: Правильная обработка числовых значений
            if ($processedSpec['dataType'] === 'number' || is_numeric($value)) {
                if (is_string($value) && str_contains($value, ',')) {
                    $normalizedValue = str_replace(',', '.', $value);
                    if (is_numeric($normalizedValue)) {
                        $processedSpec['value'] = (float) $normalizedValue;
                        $processedSpec['dataType'] = 'number';
                    }
                } elseif (is_numeric($value)) {
                    $processedSpec['value'] = (float) $value;
                    $processedSpec['dataType'] = 'number';
                }
            }

            $processed[$key] = $processedSpec;
        }

        \Log::debug('✅ Custom specifications processed', [
            'original_count' => count($specs),
            'processed_count' => count($processed)
        ]);

        return $processed;
    }

    // 🔥 ДОБАВЛЕННЫЙ МЕТОД: Конвертация из старой структуры в новую
    private function convertLegacySpecifications(array &$item)
    {
        $legacySpecs = $item['specifications'] ?? [];
        $standardSpecs = [];
        $customSpecs = [];

        foreach ($legacySpecs as $key => $value) {
            // Если ключ начинается с 'custom_', это кастомная спецификация
            if (str_starts_with($key, 'custom_')) {
                // Получаем метаданные из custom_specs_metadata
                $metadata = $item['custom_specs_metadata'][$key] ?? [];

                $customSpecs[$key] = [
                    'label' => $metadata['name'] ?? $key,
                    'value' => $value,
                    'unit' => $metadata['unit'] ?? '',
                    'dataType' => $metadata['dataType'] ?? 'string'
                ];
            } else {
                // Это стандартная спецификация
                $standardSpecs[$key] = $value;
            }
        }

        $item['standard_specifications'] = $standardSpecs;
        $item['custom_specifications'] = $customSpecs;

        \Log::debug('🔄 Legacy specifications converted', [
            'legacy_count' => count($legacySpecs),
            'standard_count' => count($standardSpecs),
            'custom_count' => count($customSpecs)
        ]);
    }

    // 🔥 ДОБАВЛЕННЫЙ МЕТОД: Извлечение значений из кастомных спецификаций для обратной совместимости
    private function extractCustomSpecValues(array $customSpecs): array
    {
        $values = [];

        foreach ($customSpecs as $key => $spec) {
            if (is_array($spec) && isset($spec['value'])) {
                $values[$key] = $spec['value'];
            }
        }

        return $values;
    }
}
