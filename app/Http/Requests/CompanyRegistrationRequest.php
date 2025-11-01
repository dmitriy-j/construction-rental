<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class CompanyRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Данные компании
            'company_type' => ['required', Rule::in(['lessor', 'lessee'])],
            'legal_name' => 'required|string|max:255',
            'tax_system' => ['required', Rule::in(['vat', 'no_vat'])],
            'inn' => 'required|digits:10',
            'kpp' => 'required|digits:9',
            'ogrn' => 'required|digits:13',
            'okpo' => 'nullable|digits_between:8,10', // Исправлено с digits:10 на digits_between:8,10
            'legal_address' => 'required|string|max:500',
            'same_as_legal' => 'sometimes|boolean',
            'actual_address' => 'required_if:same_as_legal,false|nullable|string|max:500',
            'bank_name' => 'required|string|max:255',
            'bank_account' => 'required|digits:20',
            'bik' => 'required|digits:9',
            'correspondent_account' => 'nullable|digits:20',
            'director_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|regex:/^\+?[0-9\s\-\(\)]+$/',
            'contacts' => 'nullable|string|max:500',

            // Данные пользователя
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'inn.digits' => 'ИНН должен содержать 10 цифр',
            'kpp.digits' => 'КПП должен содержать 9 цифр',
            'ogrn.digits' => 'ОГРН должен содержать 13 цифр',
            'okpo.digits_between' => 'ОКПО должен содержать 8 или 10 цифр', // Обновлено сообщение для okpo
            'bank_account.digits' => 'Расчетный счет должен содержать 20 цифр',
            'bik.digits' => 'БИК должен содержать 9 цифр',
            'phone.regex' => 'Неверный формат телефона',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован',
        ];
    }
}
