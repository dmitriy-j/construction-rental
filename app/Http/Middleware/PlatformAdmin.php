<?php

namespace App\Http\Middleware;

use Closure;

class PlatformAdmin
{
    public function handle($request, Closure $next)
    {
        if (! auth()->check() || ! auth()->user()->isPlatformAdmin()) {
            abort(403, 'Доступ запрещен');
        }

        return $next($request);
    }
}
