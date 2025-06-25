<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CompanyAuthController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register_company');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(['lessor', 'lessee'])],
            'legal_name' => 'required|string|max:255',
            'tax_system' => ['required', Rule::in(['vat', 'no_vat'])],
            'inn' => 'required|digits:10',
            'kpp' => 'required|digits:9',
            'ogrn' => 'required|digits:13',
            'okpo' => 'nullable|digits:10',
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
            'email' => 'required|string|email|max:255|unique:companies',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'inn.digits' => 'ИНН должен содержать 10 цифр',
            'kpp.digits' => 'КПП должен содержать 9 цифр',
            'ogrn.digits' => 'ОГРН должен содержать 13 цифр',
            'phone.regex' => 'Неверный формат телефона',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        if ($request->boolean('same_as_legal')) {
            $data['actual_address'] = $data['legal_address'];
        }

        Company::create([
            'type' => $data['type'],
            'legal_name' => $data['legal_name'],
            'tax_system' => $data['tax_system'],
            'inn' => $data['inn'],
            'kpp' => $data['kpp'],
            'ogrn' => $data['ogrn'],
            'okpo' => $data['okpo'] ?? null,
            'legal_address' => $data['legal_address'],
            'actual_address' => $data['actual_address'],
            'bank_name' => $data['bank_name'],
            'bank_account' => $data['bank_account'],
            'bik' => $data['bik'],
            'correspondent_account' => $data['correspondent_account'] ?? null,
            'director_name' => $data['director_name'],
            'phone' => $data['phone'],
            'contacts' => $data['contacts'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'pending',
        ]);

        return redirect()->route('login')->with('success', 'Регистрация успешно завершена!');
    }
}
