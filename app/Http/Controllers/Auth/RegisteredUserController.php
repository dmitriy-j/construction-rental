<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Http\Requests\CompanyRegistrationRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        Log::channel('registration')->debug('Отображение формы регистрации');
        return view('auth.register');
    }

    public function store(CompanyRegistrationRequest $request): RedirectResponse
    {
        Log::channel('registration')->info('🚀 НАЧАЛО РЕГИСТРАЦИИ КОМПАНИИ', [
            'company_name' => $request->legal_name,
            'email' => $request->email,
            'inn' => $request->inn,
            'legal_type' => $request->legal_type,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'all_request_data' => $request->except(['password', 'password_confirmation'])
        ]);

        DB::beginTransaction();
        Log::channel('registration')->debug('✅ Транзакция начата');

        try {
            Log::channel('registration')->debug('🔄 Начало валидации данных');
            $validatedData = $request->validated();

            Log::channel('registration')->info('✅ ДАННЫЕ ПРОШЛИ ВАЛИДАЦИЮ', [
                'company_type' => $validatedData['company_type'],
                'tax_system' => $validatedData['tax_system'],
                'legal_type' => $validatedData['legal_type'],
                'inn' => $validatedData['inn'],
                'validated_fields' => array_keys($validatedData)
            ]);

            // Определяем фактический адрес
            $actualAddress = $request->boolean('same_as_legal')
                ? $validatedData['legal_address']
                : $validatedData['actual_address'];

            // Обрабатываем KPP в зависимости от типа организации
            $kpp = null;
            if ($validatedData['legal_type'] === 'ooo') {
                $kpp = $validatedData['kpp'] ?? null;
                Log::channel('registration')->debug('🔧 KPP сохранен для ООО', ['kpp' => $kpp]);
            } else {
                Log::channel('registration')->debug('🔧 KPP пропущен для ИП');
            }

            // Создаем компанию
            Log::channel('registration')->info('🔄 СОЗДАНИЕ КОМПАНИИ В БАЗЕ ДАННЫХ');
            $company = Company::create([
                'is_lessor' => $validatedData['company_type'] === 'lessor',
                'is_lessee' => $validatedData['company_type'] === 'lessee',
                'legal_type' => $validatedData['legal_type'], // Сохраняем тип организации
                'legal_name' => $validatedData['legal_name'],
                'tax_system' => $validatedData['tax_system'],
                'inn' => $validatedData['inn'],
                'kpp' => $kpp, // KPP только для ООО
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

            Log::channel('registration')->info('✅ КОМПАНИЯ СОЗДАНА УСПЕШНО', [
                'company_id' => $company->id,
                'legal_name' => $company->legal_name,
                'legal_type' => $company->legal_type,
                'status' => $company->status
            ]);

            // Создаем пользователя
            Log::channel('registration')->info('🔄 СОЗДАНИЕ ПОЛЬЗОВАТЕЛЯ В БАЗЕ ДАННЫХ');
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'password' => Hash::make($validatedData['password']),
                'company_id' => $company->id,
            ]);

            Log::channel('registration')->info('✅ ПОЛЬЗОВАТЕЛЬ СОЗДАН УСПЕШНО', [
                'user_id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id
            ]);

            // Назначаем роль
            Log::channel('registration')->debug('🔄 НАЗНАЧЕНИЕ РОЛИ ПОЛЬЗОВАТЕЛЮ');
            $user->assignRole('company_admin');
            Log::channel('registration')->info('✅ РОЛЬ НАЗНАЧЕНА ПОЛЬЗОВАТЕЛЮ', [
                'user_id' => $user->id,
                'role' => 'company_admin'
            ]);

            // Триггерим событие регистрации
            Log::channel('registration')->debug('🔄 ТРИГГЕР СОБЫТИЯ РЕГИСТРАЦИИ');
            event(new Registered($user));
            Log::channel('registration')->debug('✅ СОБЫТИЕ РЕГИСТРАЦИИ ВЫЗВАНО');

            // ЯВНАЯ ОТПРАВКА ПИСЬМА ВЕРИФИКАЦИИ - ДОБАВЛЕНО
            Log::channel('registration')->debug('🔄 ЯВНАЯ ОТПРАВКА ПИСЬМА ВЕРИФИКАЦИИ');
            $user->sendEmailVerificationNotification();
            Log::channel('registration')->info('✅ ПИСЬМО ВЕРИФИКАЦИИ ОТПРАВЛЕНО', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // Логиним пользователя
            Log::channel('registration')->debug('🔄 АВТОРИЗАЦИЯ ПОЛЬЗОВАТЕЛЯ');
            Auth::login($user);
            Log::channel('registration')->info('✅ ПОЛЬЗОВАТЕЛЬ АВТОРИЗОВАН', ['user_id' => $user->id]);

            // Коммитим транзакцию
            Log::channel('registration')->debug('🔄 КОММИТ ТРАНЗАКЦИИ');
            DB::commit();
            Log::channel('registration')->debug('✅ ТРАНЗАКЦИЯ УСПЕШНО ЗАКОММИТЕНА');

            Log::channel('registration')->info('🎉 РЕГИСТРАЦИЯ УСПЕШНО ЗАВЕРШЕНА', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_type' => $validatedData['company_type'],
                'legal_type' => $validatedData['legal_type']
            ]);

            // Отправляем уведомление админу о новой регистрации
            try {
                app(AdminNotificationService::class)->newUserRegistered(
                    $company->legal_name,
                    $validatedData['company_type'],
                    $validatedData['director_name'],
                    $validatedData['phone'],
                    $validatedData['email']
                );
            } catch (\Throwable $e) {
                Log::error('Ошибка отправки уведомления о регистрации', ['error' => $e->getMessage()]);
            }

            // Редирект с сообщением о успешной регистрации и отправке верификации
            return redirect(RouteServiceProvider::HOME)
                ->with('status', 'registration-complete')
                ->with('message', 'Регистрация успешно завершена. Письмо с верификацией отправлено на ваш email.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::channel('registration')->error('❌ ОШИБКА ВАЛИДАЦИИ ПРИ РЕГИСТРАЦИИ', [
                'errors' => $e->errors(),
                'input_data' => $request->except(['password', 'password_confirmation'])
            ]);

            throw $e;

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::channel('registration')->error('❌ ОШИБКА БАЗЫ ДАННЫХ ПРИ РЕГИСТРАЦИИ', [
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
            Log::channel('registration')->error('💥 КРИТИЧЕСКАЯ ОШИБКА ПРИ РЕГИСТРАЦИИ', [
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
