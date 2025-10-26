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
         Commands\RecalculateProposalDelivery::class,
         Commands\FixProposalDeliveryData::class,
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

        // Ежедневная проверка кредитных лимитов в 9:00
        $schedule->command('finance:check-credit-limits')->dailyAt('09:00');

        // Ежедневное начисление пеней в 10:00
        $schedule->command('finance:calculate-late-fees')->dailyAt('10:00');

        // Ежемесячная генерация актов сверки 1-го числа в 00:00
        $schedule->command('finance:generate-reconciliation-acts')->monthlyOn(1, '00:00');

        // Ежедневная обработка банковских выписок каждый час
        $schedule->command('bank-statement:process')->hourly();

        // Еженедельные финансовые отчеты в понедельник в 08:00
        $schedule->command('finance:weekly-report')->weeklyOn(1, '08:00');

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
