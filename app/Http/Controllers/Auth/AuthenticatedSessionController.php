<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        Log::info('Showing login form');

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        Log::debug('Login attempt', ['email' => $request->email]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            Log::warning('Authentication failed', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();
        Log::info('User authenticated', [
            'user_id' => Auth::id(),
            'session_id' => session()->getId(),
        ]);

        $user = Auth::user();
        $company = $user->company;

        // ✅ ВАЖНО: НЕ блокируем вход для неподтвержденных компаний
        // Они смогут подтвердить email, но доступ к основному функционалу будет ограничен

        // Редирект в зависимости от роли и статуса
        if ($user->isPlatformAdmin()) {
            Log::info('Redirecting to admin dashboard');
            return redirect()->route('admin.dashboard');
        } elseif ($company) {
            // ✅ Разрешаем доступ к верификации email даже для неподтвержденных компаний
            if (!$user->hasVerifiedEmail()) {
                Log::info('User email not verified, redirecting to verification notice');
                return redirect()->route('verification.notice');
            }

            if ($company->status === 'verified') {
                if ($company->is_lessor) {
                    Log::info('Redirecting to lessor dashboard');
                    return redirect()->route('lessor.dashboard');
                } elseif ($company->is_lessee) {
                    Log::info('Redirecting to lessee dashboard');
                    return redirect()->route('lessee.dashboard');
                }
            } else {
                // ✅ Компания не верифицирована, но пользователь может подтвердить email
                Log::info('Company not verified, but allowing email verification', [
                    'company_status' => $company->status
                ]);
                return redirect()->route('verification.notice')
                    ->with('warning', 'Подтвердите ваш email для завершения регистрации компании.');
            }
        }

        // Fallback редирект
        Log::warning('No valid redirection found, using default');
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('User logged out');

        return redirect('/');
    }
}
