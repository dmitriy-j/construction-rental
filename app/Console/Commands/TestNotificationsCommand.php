<?php

namespace App\Console\Commands;

use App\Mail\AdminNotificationMail;
use App\Models\User;
use App\Notifications\AdminSystemNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestNotificationsCommand extends Command
{
    protected $signature = 'notifications:test';
    protected $description = 'Тестирует SMTP и database-уведомления';

    public function handle()
    {
        $this->info('=== Тест 1: Отправка email через SMTP ===');
        try {
            Mail::to('office@fap24.ru')->send(new AdminNotificationMail(
                'Тест SMTP',
                '✅ SMTP настроен и работает',
                ['Статус' => 'Успешно', 'Время' => now()->format('d.m.Y H:i:s')],
                'emails.admin-notification',
                route('admin.dashboard'),
                'Перейти в админку'
            ));
            $this->info('✅ Письмо отправлено на office@fap24.ru');
        } catch (\Throwable $e) {
            $this->error('❌ Ошибка: ' . $e->getMessage());
        }

        $this->info("\n=== Тест 2: Database-уведомление ===");
        try {
            $admin = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['platform_super', 'platform_admin']);
            })->first();

            if ($admin) {
                $admin->notify(new AdminSystemNotification(
                    'test_notification',
                    '✅ Тестовое уведомление',
                    ['Проверка' => 'Database-уведомление работает', 'Время' => now()->format('d.m.Y H:i:s')],
                    route('admin.dashboard')
                ));
                $count = $admin->unreadNotifications()->count();
                $this->info("✅ Database-уведомление создано для админа #{$admin->id}");
                $this->info("   Непрочитанных уведомлений: {$count}");
            } else {
                $this->warn('⚠️ Администратор не найден');
            }
        } catch (\Throwable $e) {
            $this->error('❌ Ошибка: ' . $e->getMessage());
        }

        $this->info("\n=== Тест 3: Конфигурация ===");
        $this->info("MAIL_MAILER: " . config('mail.default'));
        $this->info("MAIL_HOST: " . config('mail.mailers.smtp.host'));
        $this->info("MAIL_PORT: " . config('mail.mailers.smtp.port'));
        $this->info("MAIL_FROM: " . config('mail.from.address'));

        $this->info("\n✅ Тестирование завершено");
    }
}
