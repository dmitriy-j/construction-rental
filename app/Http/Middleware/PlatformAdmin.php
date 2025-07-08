<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformAdmin
{
    public function handle($request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isPlatformAdmin()) {
            abort(403, 'Доступ запрещен');
        }
        
        return $next($request);
    }
}
