<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class CompanyRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register-company');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'name' => ['required', 'string', 'max:255'],
            'vat' => ['boolean'],
            'inn' => ['required', 'string', 'digits:10'],
            'kpp' => ['nullable', 'string', 'digits:9'],
            'ogrn' => ['required', 'string', 'digits:13'], // ОГРН 13 цифр, ОГРНИП 15
            'okpo' => ['nullable', 'string', 'digits:8'],
            'legal_address' => ['required', 'string', 'max:255'],
            'actual_address' => ['required_unless:same_address,1', 'string', 'max:255'],
            'same_address' => ['boolean'],
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account' => ['required', 'string', 'digits:20'],
            'bik' => ['required', 'string', 'digits:9'],
            'correspondent_account' => ['required', 'string', 'digits:20'],
            'director' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+7\d{10}$/'],
            'manager' => ['nullable', 'string', 'max:255'],
        ]);

        // Создаем компанию
        $company = Company::create([
            'name' => $request->company_name,
            'vat' => $request->vat ?? false,
            'inn' => $request->inn,
            'kpp' => $request->kpp,
            'ogrn' => $request->ogrn,
            'okpo' => $request->okpo,
            'legal_address' => $request->legal_address,
            'actual_address' => $request->same_address ? $request->legal_address : $request->actual_address,
            'bank_name' => $request->bank_name,
            'bank_account' => $request->bank_account,
            'bik' => $request->bik,
            'correspondent_account' => $request->correspondent_account,
            'director' => $request->director,
            'phone' => $request->phone,
            'manager' => $request->manager,
        ]);

        // Создаем пользователя
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $company->id,
            'role' => 'tenant', // По умолчанию арендатор
        ]);

        // Авторизуем пользователя
        auth()->login($user);

        return redirect()->route('tenant.dashboard');
    }
}
