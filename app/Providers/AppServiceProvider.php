<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EquipmentAvailabilityService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EquipmentAvailabilityService::class, function ($app) {
        return new EquipmentAvailabilityService();
        });
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
