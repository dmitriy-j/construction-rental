<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'specifications.weight' => 'required|numeric|min:0',
            'specifications.length' => 'required|numeric|min:0',
            'specifications.width' => 'required|numeric|min:0',
            'specifications.height' => 'required|numeric|min:0',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:equipment_categories,id',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'hours_worked' => 'required|numeric|min:0',
            'price_per_hour' => 'required|numeric|min:0',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'delete_images' => 'sometimes|array',
            'delete_images.*' => 'exists:equipment_images,id',
            'location_name' => 'required|string|max:255',
            'location_address' => 'required|string|max:500',
        ];
    }
}
