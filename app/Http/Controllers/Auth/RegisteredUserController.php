<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Mail\CompanyRegisteredMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            // Данные компании
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

            // Данные пользователя
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'inn.digits' => 'ИНН должен содержать 10 цифр',
            'kpp.digits' => 'КПП должен содержать 9 цифр',
            'ogrn.digits' => 'ОГРН должен содержать 13 цифр',
            'phone.regex' => 'Неверный формат телефона',
        ]);

        DB::beginTransaction();

        try {
            // Обработка адреса
            $actualAddress = $request->same_as_legal
                ? $request->legal_address
                : $request->actual_address;

            // Создаем компанию
            $company = Company::create([
                'type' => $validatedData['type'],
                'legal_name' => $validatedData['legal_name'],
                'tax_system' => $validatedData['tax_system'],
                'inn' => $validatedData['inn'],
                'kpp' => $validatedData['kpp'],
                'ogrn' => $validatedData['ogrn'],
                'okpo' => $validatedData['okpo'] ?? null,
                'legal_address' => $validatedData['legal_address'],
                'actual_address' => $actualAddress,
                'bank_name' => $validatedData['bank_name'],
                'bank_account' => $validatedData['bank_account'],
                'bik' => $validatedData['bik'],
                'correspondent_account' => $validatedData['correspondent_account'] ?? null,
                'director_name' => $validatedData['director_name'],
                'phone' => $validatedData['phone'],
                'contacts' => $validatedData['contacts'] ?? null,
                'contact_email' => $validatedData['email'], // Используем email пользователя
                'status' => 'pending'
            ]);

            // Создаем пользователя-администратора
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'password' => Hash::make($validatedData['password']),
                'company_id' => $company->id,
                'type' => 'staff',
                'position' => 'admin'
            ]);

            // Назначаем роль
            $user->assignRole('company_admin');

            // Отправляем письмо
            /*Mail::to($user->email)->send(
                new CompanyRegisteredMail($company, $user)
            );*/

            event(new Registered($user));
            Auth::login($user);

            DB::commit();

            return redirect(RouteServiceProvider::HOME);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Ошибка регистрации: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
