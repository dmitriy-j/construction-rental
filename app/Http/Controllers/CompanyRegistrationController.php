<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Mail\CompanyRegisteredMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register-company');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:companies,email|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => 'required',
            'inn' => 'required|digits:12',
            'ogrn' => 'required|digits:13',
            'legal_address' => 'required',
            'bank_name' => 'required',
            'bank_account' => 'required|digits:20',
            'bik' => 'required|digits:9',
            'correspondent_account' => 'required|digits:20',
            'director' => 'required',
            'phone' => 'required',
        ]);

        // Создание компании
        $company = Company::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'name' => $validated['company_name'],
            'inn' => $validated['inn'],
            'ogrn' => $validated['ogrn'],
            'legal_address' => $validated['legal_address'],
            'bank_name' => $validated['bank_name'],
            'bank_account' => $validated['bank_account'],
            'bik' => $validated['bik'],
            'correspondent_account' => $validated['correspondent_account'],
            'director' => $validated['director'],
            'phone' => $validated['phone'],
        ]);

        // Создание пользователя (администратора компании)
        $user = User::create([
            'name' => $validated['director'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'type' => 'tenant',
            'role' => 'admin',
            'company_id' => $company->id,
        ]);

        // Отправка email
        Mail::to($user->email)->send(
            new CompanyRegisteredMail($company, $user)
        );

        Auth::login($user);
        return redirect()->route('tenant.dashboard');
    }
}
