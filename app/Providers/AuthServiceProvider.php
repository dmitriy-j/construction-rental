<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider; // Исправлено: добавлен правильный импорт
use App\Models\Markup; // Импортируйте вашу модель
use App\Policies\MarkupPolicy; // Импортируйте вашу политику
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        Equipment::class => EquipmentPolicy::class,
        Operator::class => OperatorPolicy::class,
        Waybill::class => WaybillPolicy::class,
        EquipmentImport::class => EquipmentImportPolicy::class,
        Markup::class => MarkupPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Определение прав для админа
        Gate::define('access-admin', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-full-request', function (User $user) {
            return $user->isLessor();
        });

        Gate::define('manage-markups', function ($user) {
            return $user->isPlatformAdmin();
        });
    }
}
