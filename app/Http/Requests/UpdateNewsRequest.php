<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:news,slug,' . $this->news->id,
            'excerpt' => 'nullable|string|max:500',
            'content' => 'sometimes|string',
            'publish_date' => 'sometimes|date',
            'is_published' => 'sometimes|boolean'
        ];
    }
}
