<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    protected $routeMiddleware = [
        // ...
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'company.lessee' => \App\Http\Middleware\CheckCompanyLessee::class,
        'company.lessor' => \App\Http\Middleware\CheckCompanyLessor::class,
        'company.verified' => \App\Http\Middleware\CheckCompanyVerified::class, // Добавь эту строку
        'shift.status' => \App\Http\Middleware\CheckShiftStatus::class,
        'idempotency' => \App\Http\Middleware\VerifyIdempotency::class,
        'credit.check' => \App\Http\Middleware\CheckCreditLimit::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'company.verified' => \App\Http\Middleware\CheckCompanyVerified::class,

        // Кастомные middleware
        'role' => \App\Http\Middleware\CheckRole::class,
        'company_admin' => \App\Http\Middleware\EnsureIsCompanyAdmin::class,
        'company.lessor' => \App\Http\Middleware\CheckCompanyLessor::class,
        'company.lessee' => \App\Http\Middleware\CheckCompanyLessee::class,
        'check.company.verified' => \App\Http\Middleware\CheckCompanyVerified::class,
        'check.company.lessee' => \App\Http\Middleware\CheckCompanyLessee::class,
        'check.company.lessor' => \App\Http\Middleware\CheckCompanyLessor::class,
        'check.user.type' => \App\Http\Middleware\CheckUserType::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'platform.admin' => \App\Http\Middleware\PlatformAdmin::class,
        'ensure.is.company.admin' => \App\Http\Middleware\EnsureIsCompanyAdmin::class,
    ];
}
