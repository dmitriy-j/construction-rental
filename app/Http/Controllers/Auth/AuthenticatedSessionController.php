<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use App\Models\Company;

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

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            Log::warning('Authentication failed', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();
        Log::info('User authenticated', [
            'user_id' => Auth::id(),
            'session_id' => session()->getId()
        ]);

        $user = Auth::user();
        $company = $user->company;

        // Проверка статуса компании - ДОБАВЛЕНО ВОЗВРАТ РЕДИРЕКТА
        if ($company && $company->status !== 'verified') {
            Auth::logout();
            Log::warning('Company not verified', [
                'user_id' => $user->id,
                'company_status' => $company->status
            ]);
            return back()->withErrors([ // ВОЗВРАТ ЗДЕСЬ
                'email' => 'Ваша компания не прошла верификацию'
            ]);
        }

        // Подробное логирование
        Log::debug('User details', [
            'id' => $user->id,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'is_platform_admin' => $user->isPlatformAdmin()
        ]);

        if ($company) {
            Log::debug('Company details', [
                'id' => $company->id,
                'is_lessor' => $company->is_lessor,
                'is_lessee' => $company->is_lessee,
                'status' => $company->status
            ]);
        } else {
            Log::warning('User has no company associated', ['user_id' => $user->id]);
        }

        // Исправленный блок редиректов с возвратом
        if ($user->isPlatformAdmin()) {
            Log::info('Redirecting to admin dashboard');
            return redirect()->route('admin.dashboard'); // ВОЗВРАТ
        } elseif ($company) {
            if ($company->is_lessor) {
                Log::info('Redirecting to lessor dashboard');
                return redirect()->route('lessor.dashboard'); // ВОЗВРАТ
            } elseif ($company->is_lessee) {
                Log::info('Redirecting to lessee dashboard');
                return redirect()->route('lessee.dashboard'); // ВОЗВРАТ
            }
        }

        // Fallback редирект с возвратом
        Log::warning('No valid redirection found, using default');
        return redirect()->intended(RouteServiceProvider::HOME); // ВОЗВРАТ
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