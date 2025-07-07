<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckCompanyLessor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user || !$user->company || !$user->company->is_lessor) {
            abort(403, 'Доступ только для компаний-арендодателей');
        }

        return $next($request);
    }
}