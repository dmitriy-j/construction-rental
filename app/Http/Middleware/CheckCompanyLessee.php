<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckCompanyLessee
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user || !$user->company || !$user->company->is_lessee || !$user->company->verified_at) {
            abort(403, 'Доступ только для верифицированных компаний-арендаторов');
        }

        return $next($request);
    }
}
