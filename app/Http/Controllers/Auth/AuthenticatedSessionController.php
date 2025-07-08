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

    public function store(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        return redirect()->route('home')->with('success', 'Успешный вход!');
    }

    return back()->withErrors(['email' => 'Неверные данные']);
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