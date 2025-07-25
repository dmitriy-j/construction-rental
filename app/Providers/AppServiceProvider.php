<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EquipmentAvailabilityService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

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

        $this->app->bind(TransportCalculatorService::class, function () {
            return new TransportCalculatorService();
        });

        $this->app->bind(DeliveryCalculatorService::class, function () {
            return new DeliveryCalculatorService();
        });
        //
    }

    /**
     * Bootstrap any application services.
     */
   // public function boot(): void
   // {
        //
 //   }

    public function boot()
    {
    Paginator::useBootstrapFive();

        // Логирование всех необработанных исключений
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR])) {
                \Log::emergency('SHUTDOWN ERROR', $error);
            }
        });

    }
}
