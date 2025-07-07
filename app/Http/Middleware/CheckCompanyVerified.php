<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckCompanyVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if ($user && $user->company && $user->company->status !== 'verified') {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Ваша компания не прошла верификацию']);
        }

        return $next($request);
    }
}