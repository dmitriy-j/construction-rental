<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        \App\Console\Commands\FillWaybillOrderItems::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->call(function () {
        \App\Models\Waybill::where('end_date', '<', now())
            ->where('status', \App\Models\Waybill::STATUS_ACTIVE)
            ->each(function ($waybill) {
                $waybill->update(['status' => \App\Models\Waybill::STATUS_COMPLETED]);
                \Log::info('Путевой лист автоматически завершен', ['id' => $waybill->id]);
            });
    })->daily();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
