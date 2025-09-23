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
            'category_id' => 'required|exists:equipment_categories,id',
            'rental_period_start' => 'required|date|after:today',
            'rental_period_end' => 'required|date|after:rental_period_start',
            'budget_from' => 'required|numeric|min:0',
            'budget_to' => 'required|numeric|min:budget_from',
            'location_id' => 'required|exists:locations,id',
            'delivery_required' => 'boolean',
            'specifications' => 'nullable|array'
        ];
    }

    public function prepareForValidation()
    {
        \Log::info('Raw request data:', $this->all());

        $this->merge([
            'budget_from' => $this->normalizeNumber($this->budget_from),
            'budget_to' => $this->normalizeNumber($this->budget_to),
            'delivery_required' => $this->boolean('delivery_required'),
        ]);

        \Log::info('Processed request data:', [
            'budget_from' => $this->budget_from,
            'budget_to' => $this->budget_to,
            'delivery_required' => $this->delivery_required,
        ]);
    }

    public function messages()
    {
        return [
            'budget_from.numeric' => 'Поле "Бюджет от" должно быть числом',
            'budget_to.numeric' => 'Поле "Бюджет до" должно быть числом',
            'budget_to.min' => 'Поле "Бюджет до" должно быть больше поля "Бюджет от"',
        ];
    }

    private function normalizeNumber($value)
    {
        \Log::info("Normalizing value:", ['raw_value' => $value, 'type' => gettype($value)]);

        if ($value === null || $value === '') {
            return 0;
        }

        // Если уже число - возвращаем как есть
        if (is_numeric($value)) {
            \Log::info("Value is already numeric", ['value' => $value]);
            return $value;
        }

        // Преобразуем в строку
        $value = (string) $value;
        \Log::info("Value as string", ['string_value' => $value]);

        // Убираем все пробелы (разделители тысяч)
        $value = str_replace(' ', '', $value);

        // Заменяем русскую запятую на точку
        $value = str_replace(',', '.', $value);

        // Убираем все нечисловые символы кроме цифр, точки и минуса
        $value = preg_replace('/[^\d\.\-]/', '', $value);

        \Log::info("Value after cleaning", ['cleaned_value' => $value]);

        // Если после обработки пустая строка или только минус
        if ($value === '' || $value === '-') {
            return 0;
        }

        // Проверяем, является ли результат числом
        if (!is_numeric($value)) {
            \Log::warning("Value is not numeric after normalization", ['value' => $value]);

            // Пробуем извлечь число с помощью filter_var
            $filtered = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            if ($filtered !== false && is_numeric($filtered)) {
                \Log::info("Value filtered successfully", ['filtered' => $filtered]);
                return $filtered;
            }

            return 0;
        }

        $result = $value;
        \Log::info("Normalization successful", ['result' => $result, 'type' => gettype($result)]);

        return $result;
    }
}
