<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyLessee
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Требуется авторизация');
        }

        if (!$user->company) {
            abort(403, 'Пользователь не привязан к компании');
        }

        if (!$user->company->is_lessee) {
            abort(403, 'Доступ только для компаний-арендаторов');
        }

        if ($user->company->status !== 'verified') {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Ваша компания не прошла верификацию']);
        }

        return $next($request);
    }
}
