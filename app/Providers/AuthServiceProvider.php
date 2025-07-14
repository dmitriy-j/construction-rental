<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate; // Исправлено: добавлен правильный импорт
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
    }
}
