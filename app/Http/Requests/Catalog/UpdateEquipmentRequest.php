<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:equipment_categories,id',
            'location_id' => 'required|exists:locations,id',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1950|max:' . (date('Y') + 1),
            'hours_worked' => 'required|numeric|min:0',
            'images' => 'sometimes|array|min:1|max:10',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
