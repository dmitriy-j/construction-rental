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
            // Тип компании
            'company_type' => 'required|in:lessor,lessee,carrier',

            // Тип организации
            'legal_type' => 'required|in:ip,ooo',

            // Основная информация
            'legal_name' => 'required|string|max:255',
            'tax_system' => 'required|in:vat,no_vat',

            // ИНН с условной валидацией
            'inn' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
                function ($attribute, $value, $fail) {
                    if ($this->legal_type === 'ip' && strlen($value) !== 12) {
                        $fail('Для ИП ИНН должен содержать 12 цифр');
                    }
                    if ($this->legal_type === 'ooo' && strlen($value) !== 10) {
                        $fail('Для ООО ИНН должен содержать 10 цифр');
                    }
                },
                'unique:companies,inn',
            ],

            // КПП только для ООО
            'kpp' => [
                'required_if:legal_type,ooo',
                'nullable',
                'string',
                'regex:/^[0-9]+$/',
                function ($attribute, $value, $fail) {
                    if ($this->legal_type === 'ooo' && (!empty($value) && strlen($value) !== 9)) {
                        $fail('КПП должен содержать 9 цифр');
                    }
                },
            ],

            // ОГРН с условной валидацией
            'ogrn' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
                function ($attribute, $value, $fail) {
                    if ($this->legal_type === 'ip' && strlen($value) !== 15) {
                        $fail('Для ИП ОГРН должен содержать 15 цифр');
                    }
                    if ($this->legal_type === 'ooo' && strlen($value) !== 13) {
                        $fail('Для ООО ОГРН должен содержать 13 цифр');
                    }
                },
                'unique:companies,ogrn',
            ],

            // ОКПО
            'okpo' => 'nullable|string|regex:/^[0-9]+$/|min:8|max:10',

            // Адреса
            'legal_address' => 'required|string|max:500',
            'same_as_legal' => 'sometimes|boolean',
            'actual_address' => 'required_if:same_as_legal,false|nullable|string|max:500',

            // Банковские реквизиты
            'bank_name' => 'required|string|max:255',
            'bank_account' => 'required|string|regex:/^[0-9]+$/|size:20',
            'bik' => 'required|string|regex:/^[0-9]+$/|size:9',
            'correspondent_account' => 'nullable|string|regex:/^[0-9]+$/|size:20',

            // Контактная информация компании
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
            // Общие сообщения
            'company_type.required' => 'Пожалуйста, выберите тип компании',
            'company_type.in' => 'Выбран недопустимый тип компании',

            'legal_type.required' => 'Пожалуйста, выберите тип организации',
            'legal_type.in' => 'Тип организации должен быть ИП или ООО',

            'tax_system.required' => 'Пожалуйста, выберите систему налогообложения',
            'tax_system.in' => 'Выбрана недопустимая система налогообложения',

            // Сообщения для числовых полей
            'inn.regex' => 'ИНН должен содержать только цифры',
            'inn.unique' => 'Компания с таким ИНН уже зарегистрирована',

            'kpp.required_if' => 'Поле КПП обязательно для ООО',
            'kpp.regex' => 'КПП должен содержать только цифры',

            'ogrn.regex' => 'ОГРН должен содержать только цифры',
            'ogrn.unique' => 'Компания с таким ОГРН уже зарегистрирована',

            'okpo.regex' => 'ОКПО должен содержать только цифры',
            'okpo.min' => 'ОКПО должен содержать не менее 8 цифр',
            'okpo.max' => 'ОКПО должен содержать не более 10 цифр',

            'bank_account.regex' => 'Расчетный счет должен содержать только цифры',
            'bank_account.size' => 'Расчетный счет должен содержать 20 цифр',

            'bik.regex' => 'БИК должен содержать только цифры',
            'bik.size' => 'БИК должен содержать 9 цифр',

            'correspondent_account.regex' => 'Корреспондентский счет должен содержать только цифры',
            'correspondent_account.size' => 'Корреспондентский счет должен содержать 20 цифр',

            // Сообщения для адресов
            'actual_address.required_if' => 'Поле фактический адрес обязательно, если адреса не совпадают',

            // Сообщения для контактов
            'phone.regex' => 'Неверный формат телефона',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован',
        ];
    }

    public function attributes(): array
    {
        return [
            'company_type' => 'тип компании',
            'legal_type' => 'тип организации',
            'legal_name' => 'название компании',
            'tax_system' => 'система налогообложения',
            'inn' => 'ИНН',
            'kpp' => 'КПП',
            'ogrn' => 'ОГРН',
            'okpo' => 'ОКПО',
            'legal_address' => 'юридический адрес',
            'actual_address' => 'фактический адрес',
            'bank_name' => 'название банка',
            'bank_account' => 'расчетный счет',
            'bik' => 'БИК',
            'correspondent_account' => 'корреспондентский счет',
            'director_name' => 'ФИО директора',
            'phone' => 'телефон',
            'contacts' => 'контактное лицо',
            'name' => 'имя',
            'email' => 'email',
            'password' => 'пароль',
        ];
    }

    /**
     * Подготовка данных для валидации
     */
    protected function prepareForValidation()
    {
        // Если чекбокс отмечен, устанавливаем actual_address в null
        if ($this->has('same_as_legal') && $this->boolean('same_as_legal')) {
            $this->merge([
                'actual_address' => null,
            ]);
        }
    }
}
