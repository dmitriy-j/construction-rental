<?php

namespace App\Http\Requests\Catalog; // Исправлено

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:equipment_categories,id',
            'location_id' => 'required|exists:locations,id',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:'.(date('Y')+1),
            'hours_worked' => 'required|numeric|min:0',
            'price_per_hour' => 'required|numeric|min:0',
            'images' => 'required|array|min:1',
            //'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif'
        ];
    }
}
