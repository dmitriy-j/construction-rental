<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserType
{
    public function handle(Request $request, Closure $next, ...$types)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Требуется авторизация');
        }

        // Проверяем тип через компанию
        $userType = null;
        if ($user->company) {
            if ($user->company->is_lessee) {
                $userType = 'lessee';
            } elseif ($user->company->is_lessor) {
                $userType = 'lessor';
            }
        }

        // Проверяем администратора платформы
        if ($user->isPlatformAdmin()) {
            $userType = 'admin';
        }

        if (! $userType || ! in_array($userType, $types)) {
            abort(403, 'Доступ запрещен для вашего типа пользователя');
        }

        return $next($request);
    }
}
