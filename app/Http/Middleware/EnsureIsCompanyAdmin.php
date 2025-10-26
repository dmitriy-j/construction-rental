<?php

// app/Http/Middleware/EnsureIsCompanyAdmin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureIsCompanyAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Пользователь должен быть аутентифицирован
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        // 2. Пользователь должен быть администратором компании
        if (auth()->user()->role !== 'company_admin') {
            abort(403, 'Доступ запрещён');
        }

        return $next($request);
    }
}
