<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['platform_super', 'platform_admin']);
    }

    public function rules(): array
    {
        $contractId = $this->route('contract') ? $this->route('contract')->id : null;

        return [
            'number' => 'required|string|max:255|unique:contracts,number,' . $contractId,
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'payment_type' => 'required|in:prepay,postpay,mixed',
            'documentation_deadline' => 'required|integer|min:1',
            'payment_deadline' => 'required|integer|min:1',
            'penalty_rate' => 'required|numeric|min:0|max:100',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'is_active' => 'boolean',
            'counterparty_type' => 'required|in:lessor,lessee',
            'counterparty_company_id' => 'required|exists:companies,id',
        ];
    }

    public function messages(): array
    {
        return [
            'counterparty_company_id.required' => 'Выберите компанию-контрагента',
            'counterparty_type.required' => 'Выберите тип контрагента',
            'end_date.after' => 'Дата окончания должна быть после даты начала',
            'number.unique' => 'Договор с таким номером уже существует',
        ];
    }

    public function attributes(): array
    {
        return [
            'number' => 'номер договора',
            'start_date' => 'дата начала',
            'end_date' => 'дата окончания',
            'payment_type' => 'тип оплаты',
            'counterparty_type' => 'тип контрагента',
            'counterparty_company_id' => 'контрагент',
        ];
    }
}
