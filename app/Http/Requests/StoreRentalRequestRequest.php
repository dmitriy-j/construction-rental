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
            'hourly_rate' => 'required|numeric|min:0', // Базовая стоимость
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

        $this->merge([
            'hourly_rate' => (float) str_replace(',', '.', $this->hourly_rate),
            'delivery_required' => $deliveryRequired,
            'rental_conditions' => $rentalConditions,
        ]);

        \Log::debug('After prepareForValidation:', [
            'delivery_required' => $deliveryRequired,
            'rental_conditions' => $rentalConditions
        ]);
    }
}
