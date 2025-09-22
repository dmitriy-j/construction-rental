<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! $idempotencyKey) {
            return response()->json([
                'message' => 'Idempotency-Key header is required',
            ], 400);
        }

        // Проверяем, не обрабатывался ли уже этот ключ
        if (Cache::has('idempotency:'.$idempotencyKey)) {
            return response()->json([
                'message' => 'Duplicate request',
            ], 409);
        }

        // Сохраняем ключ на короткое время
        Cache::put('idempotency:'.$idempotencyKey, true, 60);

        return $next($request);
    }
}
