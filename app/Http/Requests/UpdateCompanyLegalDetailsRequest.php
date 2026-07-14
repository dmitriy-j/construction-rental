<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyLegalDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Авторизуем запрос – пользователь уже аутентифицирован через маршрут с middleware 'auth'
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'legal_type'    => ['required', 'string', Rule::in(['ooo', 'ip'])],
            'legal_name'    => ['required', 'string', 'max:255'],
            'tax_system'    => ['required', 'string', Rule::in(['vat', 'no_vat'])],
            'inn'           => ['required', 'string', 'max:12', 'regex:/^\d+$/'],
            'kpp'           => [
                'nullable',                  // необязательно в целом, но...
                'required_if:legal_type,ooo', // ...обязательно, если ООО
                'string', 'size:9', 'regex:/^\d+$/',
            ],
            'ogrn'          => ['required', 'string', 'max:15', 'regex:/^\d+$/'],
            'okpo'          => ['nullable', 'string', 'max:255'],
            'legal_address' => ['required', 'string', 'max:500'],
            'actual_address'=> ['nullable', 'string', 'max:500'],
            'same_as_legal' => ['nullable', 'boolean'],
            'director_name' => ['required', 'string', 'max:255'],
            'contacts'      => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom error messages (optional).
     */
    public function messages(): array
    {
        return [
            'kpp.required_if' => 'Поле КПП обязательно для ООО.',
            'kpp.size'        => 'КПП должен содержать 9 цифр.',
            'inn.max'         => 'ИНН должен содержать не более 12 цифр.',
            'ogrn.max'        => 'ОГРН/ОГРНИП должен содержать не более 15 цифр.',
            'inn.regex'       => 'ИНН должен содержать только цифры.',
            'ogrn.regex'      => 'ОГРН/ОГРНИП должен содержать только цифры.',
        ];
    }
}