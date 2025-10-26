<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Http\Requests\CompanyRegistrationRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View; // Добавлен импорт View
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(CompanyRegistrationRequest $request): RedirectResponse
    {
        Log::channel('registration')->info('Начало регистрации компании', [
            'company_name' => $request->legal_name,
            'email' => $request->email,
            'inn' => $request->inn,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        DB::beginTransaction();

        try {
            $validatedData = $request->validated();

            Log::debug('Данные прошли валидацию', [
                'company_type' => $validatedData['company_type'],
                'tax_system' => $validatedData['tax_system'],
                'inn' => $validatedData['inn']
            ]);

            // Определяем фактический адрес
            $actualAddress = $request->boolean('same_as_legal')
                ? $validatedData['legal_address']
                : $validatedData['actual_address'];

            Log::debug('Адреса компании определены', [
                'legal_address' => $validatedData['legal_address'],
                'actual_address' => $actualAddress,
                'same_as_legal' => $request->boolean('same_as_legal')
            ]);

            // Создаем компанию
            $company = Company::create([
                'is_lessor' => $validatedData['company_type'] === 'lessor',
                'is_lessee' => $validatedData['company_type'] === 'lessee',
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
                'status' => 'pending',
            ]);

            Log::info('Компания создана успешно', [
                'company_id' => $company->id,
                'legal_name' => $company->legal_name,
                'status' => $company->status
            ]);

            // Создаем пользователя
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'password' => Hash::make($validatedData['password']),
                'company_id' => $company->id,
            ]);

            Log::debug('Пользователь создан', [
                'user_id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id
            ]);

            // Назначаем роль
            $user->assignRole('company_admin');
            Log::debug('Роль назначена пользователю', [
                'user_id' => $user->id,
                'role' => 'company_admin'
            ]);

            // Триггерим событие регистрации
            event(new Registered($user));

            // Логиним пользователя
            Auth::login($user);
            Log::debug('Пользователь авторизован', ['user_id' => $user->id]);

            DB::commit();

            Log::channel('registration')->info('Регистрация успешно завершена', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_type' => $validatedData['company_type']
            ]);

            return redirect(RouteServiceProvider::HOME);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            Log::channel('registration')->error('Ошибка валидации при регистрации', [
                'errors' => $e->errors(),
                'input_data' => $request->except(['password', 'password_confirmation'])
            ]);

            throw $e;

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            Log::channel('registration')->error('Ошибка базы данных при регистрации', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'sql_query' => $e->getSql(),
                'bindings' => $e->getBindings()
            ]);

            return back()
                ->withErrors(['error' => 'Ошибка базы данных при регистрации. Пожалуйста, попробуйте позже.'])
                ->withInput();

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('registration')->error('Критическая ошибка при регистрации', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withErrors(['error' => 'Произошла непредвиденная ошибка. Пожалуйста, попробуйте позже или обратитесь в поддержку.'])
                ->withInput();
        }
    }
}
