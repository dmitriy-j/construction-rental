<?php
// app/Http/Requests/ProfileUpdateRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255',
                        Rule::unique('users')->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+7\d{10}$/'],
            'position' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Телефон должен быть в формате +7XXXXXXXXXX',
        ];
    }
}
