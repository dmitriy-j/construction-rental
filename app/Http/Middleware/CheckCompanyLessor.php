<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyLessor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Проверяем что пользователь аутентифицирован
        if (! $user) {
            abort(403, 'Требуется авторизация');
        }

        // Проверяем наличие компании
        if (! $user->company) {
            abort(403, 'Пользователь не привязан к компании');
        }

        // Проверяем что компания является арендодателем
        if (! $user->company->is_lessor) {
            abort(403, 'Доступ только для компаний-арендодателей');
        }

        return $next($request);
    }
}
