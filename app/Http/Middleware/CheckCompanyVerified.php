<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Пропускаем platform администраторов
        if ($user && $user->isPlatformAdmin()) {
            return $next($request);
        }

        if ($user && $user->company && $user->company->status !== 'verified') {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['email' => 'Ваша компания не прошла верификацию']);
        }

        return $next($request);
    }
}
