<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

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
            'items.*.specifications' => 'sometimes|array',

            // ⚠️ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Обновленные правила для спецификаций и метаданных
            'items.*.specifications' => 'sometimes|array',
            'items.*.specifications.*' => 'nullable',

            // ⚠️ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Правила для метаданных
            'items.*.custom_specs_metadata' => 'sometimes|array',
            'items.*.custom_specs_metadata.*' => 'sometimes|array',
            'items.*.custom_specs_metadata.*.name' => 'sometimes|string|max:255',
            'items.*.custom_specs_metadata.*.dataType' => 'sometimes|in:string,number',
            'items.*.custom_specs_metadata.*.unit' => 'sometimes|string|max:50',

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

    public function messages()
    {
        return [
            'items.*.specifications.*.numeric' => 'Значение параметра ":attribute" должно быть числом',
            'items.*.hourly_rate.numeric' => 'Стоимость часа должна быть числом',
            'hourly_rate.numeric' => 'Базовая стоимость часа должна быть числом',
        ];
    }

    public function prepareForValidation()
    {
        \Log::debug('🔄 prepareForValidation started', [
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

        // ⚠️ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Улучшенная обработка спецификаций и метаданных
        $items = $this->input('items', []);
        foreach ($items as &$item) {
            \Log::debug('🔧 Processing item', [
                'category_id' => $item['category_id'] ?? 'unknown',
                'has_specifications' => !empty($item['specifications']),
                'has_metadata' => !empty($item['custom_specs_metadata']),
                'metadata_keys' => array_keys($item['custom_specs_metadata'] ?? [])
            ]);

            // Обрабатываем спецификации
            if (isset($item['specifications']) && is_array($item['specifications'])) {
                $item['specifications'] = collect($item['specifications'])->map(function ($value, $key) use ($item) {
                    if ($value === '' || $value === null) {
                        return null;
                    }

                    // ⚠️ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Определяем тип данных из метаданных
                    $dataType = $item['custom_specs_metadata'][$key]['dataType'] ?? null;

                    if ($dataType === 'number') {
                        return is_numeric($value) ? (float) $value : null;
                    }

                    // Для текстовых значений оставляем как есть
                    return $value;
                })->filter(function ($value) {
                    return $value !== null && $value !== '';
                })->toArray();
            }

            // ⚠️ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Обрабатываем метаданные
            if (isset($item['custom_specs_metadata']) && is_array($item['custom_specs_metadata'])) {
                $item['custom_specs_metadata'] = collect($item['custom_specs_metadata'])->map(function ($metadata, $key) {
                    return [
                        'name' => $metadata['name'] ?? '',
                        'dataType' => $metadata['dataType'] ?? 'string',
                        'unit' => $metadata['unit'] ?? ''
                    ];
                })->filter(function ($metadata) {
                    // Убираем пустые метаданные
                    return !empty($metadata['name']) || !empty($metadata['unit']);
                })->toArray();
            } else {
                $item['custom_specs_metadata'] = [];
            }

            // Обрабатываем hourly_rate
            if (isset($item['hourly_rate'])) {
                $item['hourly_rate'] = (float) str_replace(',', '.', $item['hourly_rate']);
            }

            \Log::debug('✅ Item processed', [
                'final_specs_count' => count($item['specifications'] ?? []),
                'final_metadata_count' => count($item['custom_specs_metadata'] ?? [])
            ]);
        }

        $this->merge([
            'hourly_rate' => (float) str_replace(',', '.', $this->hourly_rate),
            'delivery_required' => $deliveryRequired,
            'rental_conditions' => $rentalConditions,
            'items' => $items,
        ]);

        \Log::debug('✅ prepareForValidation completed', [
            'final_items_count' => count($items),
            'has_metadata_in_final' => !empty($items[0]['custom_specs_metadata'] ?? [])
        ]);
    }
}
