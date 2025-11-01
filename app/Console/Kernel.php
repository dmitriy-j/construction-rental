<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        \App\Console\Commands\FillWaybillOrderItems::class,
        Commands\RecalculateProposalDelivery::class,
        Commands\FixProposalDeliveryData::class,
        \App\Console\Commands\TestEmail::class,
        \App\Console\Commands\MonitorQueue::class, // Добавлена новая команда
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Основной queue worker для обработки email и других заданий
        $schedule->command('queue:work --queue=high,default,low --tries=3 --timeout=60 --sleep=3')
                 ->everyMinute()
                 ->runInBackground()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/queue-worker.log'))
                 ->onSuccess(function () {
                     Log::channel('queue')->debug('Queue worker запущен успешно');
                 })
                 ->onFailure(function () {
                     Log::channel('queue')->error('Ошибка запуска queue worker');
                 });

        // Мониторинг состояния очереди каждые 5 минут
        $schedule->command('queue:monitor default,high,low --max=100')
                 ->everyFiveMinutes()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/queue-monitor.log'));

        // Очистка завершенных заданий очереди каждые 10 минут
        $schedule->command('queue:prune-failed --hours=24')
                 ->everyTenMinutes()
                 ->runInBackground();

        // Очистка устаревших batch заданий каждые 30 минут
        $schedule->command('queue:prune-batches --hours=48 --unfinished=72')
                 ->everyThirtyMinutes()
                 ->runInBackground();

        // Существующие задачи - оставляем без изменений
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
