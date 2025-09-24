<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\BudgetComparison;

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
            'budget_to' => 'required|numeric', // Убрали min:budget_from
            'location_id' => 'required|exists:locations,id',
            'delivery_required' => 'boolean',
            'specifications' => 'nullable|string' // ИЗМЕНИЛИ array на string
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Кастомная проверка сравнения бюджетов
            if ($this->budget_to <= $this->budget_from) {
                $validator->errors()->add(
                    'budget_to',
                    'Поле "Бюджет до" должно быть больше поля "Бюджет от".'
                );
            }
        });
    }

    public function prepareForValidation()
    {
        \Log::info('Raw request data:', $this->all());

        $this->merge([
            'budget_from' => $this->normalizeNumber($this->budget_from),
            'budget_to' => $this->normalizeNumber($this->budget_to),
            'delivery_required' => $this->boolean('delivery_required'),
            // УБИРАЕМ преобразование specifications в массив
        ]);

        \Log::info('Processed request data:', [
            'budget_from' => $this->budget_from,
            'budget_to' => $this->budget_to,
            'delivery_required' => $this->delivery_required,
            'specifications' => $this->specifications // Оставляем как строку
        ]);
    }

    private function normalizeNumber($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = (string) $value;
        $value = str_replace([' ', ','], ['', '.'], $value);
        $value = preg_replace('/[^\d\.\-]/', '', $value);

        if ($value === '' || $value === '-') {
            return 0;
        }

        return is_numeric($value) ? (float) $value : 0;
    }
}
