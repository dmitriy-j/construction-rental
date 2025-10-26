<?php

namespace App\Providers;

use App\Jobs\ExportUpdTo1C;
use App\Models\Invoice;
use App\Models\Upd;
use App\Models\WaybillShift; // Добавляем импорт модели
// Импорт наблюдателя
use App\Services\EquipmentAvailabilityService;
use App\Services\OneCExportService;
use App\Services\WaybillCreationService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EquipmentAvailabilityService::class, function ($app) {
            return new EquipmentAvailabilityService;
        });

        $this->app->bind(TransportCalculatorService::class, function () {
            return new TransportCalculatorService;
        });

        $this->app->bind(DeliveryCalculatorService::class, function () {
            return new DeliveryCalculatorService;
        });

        $this->app->bind(DeliveryNoteService::class, function ($app) {
            return new DeliveryNoteService;
        });

        $this->app->bind(WaybillCreationService::class, function ($app) {
            return new WaybillCreationService;
        });

        $this->app->singleton(BalanceService::class, function ($app) {
            return new BalanceService;
        });

        $this->app->singleton(FinancialAnalyticsService::class, function ($app) {
            return new FinancialAnalyticsService;
        });
        $this->app->singleton(\App\Services\Parsers\UpdParserService::class, function ($app) {
            return new \App\Services\Parsers\UpdParserService;
        });

        $this->app->singleton(UpdProcessingService::class, function ($app) {
            return new UpdProcessingService(
                $app->make(BalanceService::class),
                $app->make(\App\Services\Parsers\UpdParserService::class)
            );
        });

        $this->app->singleton(OneCIntegrationService::class, function ($app) {
            return new OneCIntegrationService;
        });

        $this->app->singleton(OneCExportService::class, function ($app) {
            return new OneCExportService;
        });

        View::composer('partials.sidebar', function ($view) {
            if (Auth::check() && Auth::user()->company && Auth::user()->company->is_lessor) {
                $pendingUpdsCount = Upd::where('lessor_company_id', Auth::user()->company->id)
                    ->where('status', 'pending')
                    ->count();

                $view->with('pendingUpdsCount', $pendingUpdsCount);
            } else {
                $view->with('pendingUpdsCount', 0);
            }
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
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR])) {
                \Log::emergency('SHUTDOWN ERROR', $error);
            }
        });

        WaybillShift::observe(\App\Observers\WaybillShiftObserver::class);

        // Автоматическая выгрузка в 1С при принятии УПД
        Upd::updated(function ($upd) {
            if ($upd->isDirty('status') && $upd->status === Upd::STATUS_ACCEPTED) {
                // Используем очередь для экспорта
                ExportUpdTo1C::dispatch($upd);
            }
        });

        // Автоматическая выгрузка в 1С при оплате счета
        Invoice::updated(function ($invoice) {
            if ($invoice->isDirty('status') && $invoice->status === Invoice::STATUS_PAID) {
                $oneCService = app(\App\Services\OneCIntegrationService::class);
                $data = $oneCService->exportInvoice($invoice);
                $oneCService->sendTo1C($data, 'invoice');
            }
        });

    }
}
