<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyBankDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->company_id !== null;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account' => ['required', 'string', 'max:20', 'regex:/^[0-9]{20}$/'],
            'bik' => ['required', 'string', 'size:9', 'regex:/^[0-9]{9}$/'],
            'correspondent_account' => ['required', 'string', 'max:20', 'regex:/^[0-9]{20}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_account.regex' => 'Расчетный счет должен содержать ровно 20 цифр',
            'bik.size' => 'БИК должен содержать ровно 9 цифр',
            'bik.regex' => 'БИК должен содержать только цифры',
            'correspondent_account.regex' => 'Корреспондентский счет должен содержать 20 цифр',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->bik && $this->correspondent_account) {
                if (!$this->validateCorrespondentAccount()) {
                    $validator->errors()->add(
                        'correspondent_account',
                        'Корреспондентский счет не соответствует БИК банка'
                    );
                }
            }
        });
    }

    private function validateCorrespondentAccount(): bool
    {
        // Проверка соответствия кор. счета БИКу (первые 3 цифры = 301, следующие 3 = последние 3 цифры БИК)
        $bik = $this->bik;
        $correspondent = $this->correspondent_account;

        return substr($correspondent, 0, 3) === '301' &&
               substr($correspondent, 3, 3) === substr($bik, -3);
    }
}
