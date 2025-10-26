<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Если пользователь уже аутентифицирован, перенаправляем в ЛК
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->isPlatformAdmin()) {
                return route('admin.dashboard');
            }

            if ($user->company) {
                if ($user->company->is_lessor) {
                    return route('lessor.dashboard');
                }
                if ($user->company->is_lessee) {
                    return route('lessee.dashboard');
                }
            }
        }

        return route('login');
    }
}
