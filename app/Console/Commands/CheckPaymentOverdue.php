<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\AdminNotificationService;
use Illuminate\Console\Command;

class CheckPaymentOverdue extends Command
{
    protected $signature = 'notifications:check-overdue';
    protected $description = 'Проверяет заказы с просрочкой оплаты более 15 дней и отправляет уведомления';

    public function handle(AdminNotificationService $notificationService)
    {
        $this->info('Проверка просрочек оплаты...');

        $overdueOrders = Order::whereIn('status', ['active', 'confirmed'])
            ->whereDoesntHave('completionAct')
            ->where('created_at', '<', now()->subDays(15))
            ->get();

        $count = 0;
        foreach ($overdueOrders as $order) {
            $lesseeName = $order->lessee?->legal_name ?? 'Неизвестно';

            $notificationService->paymentOverdue(
                $order->number ?? "#{$order->id}",
                $order->total_amount ?? 0,
                $lesseeName
            );

            $count++;
        }

        $this->info("Обработано просрочек: {$count}");

        return Command::SUCCESS;
    }
}
