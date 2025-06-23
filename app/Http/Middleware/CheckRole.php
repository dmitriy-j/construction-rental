<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
        public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // 1. Проверка аутентификации
        if (!$user) {
            return redirect()->route('login');
        }

        // 2. Проверка типа пользователя (только сотрудники)
        if ($user->type !== 'staff') { // <-- Убедитесь в этой строке
            abort(403, 'Access denied: Staff only');
        }

        // 3. Проверка ролей
        if (!in_array($user->role, $roles)) {
            $rolesList = implode(', ', $roles);
            abort(403, "Forbidden. Required roles: {$rolesList}. Your role: {$user->role}");
        }

        return $next($request);
    }
}
