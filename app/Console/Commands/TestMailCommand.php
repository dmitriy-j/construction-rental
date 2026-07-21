<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email?}';
    protected $description = 'Отправляет тестовое письмо для проверки настроек SMTP';

    public function handle()
    {
        $email = $this->argument('email') ?: env('ADMIN_EMAIL', 'office@fap24.ru');

        $this->info("Отправка тестового письма на {$email}...");
        $this->line("MAIL_MAILER: " . env('MAIL_MAILER'));
        $this->line("MAIL_HOST: " . env('MAIL_HOST'));
        $this->line("MAIL_PORT: " . env('MAIL_PORT'));
        $this->line("MAIL_ENCRYPTION: " . env('MAIL_ENCRYPTION'));

        try {
            Mail::raw('Это тестовое письмо для проверки настроек SMTP.', function ($msg) use ($email) {
                $msg->to($email)->subject('Тест SMTP — ' . now()->format('d.m.Y H:i'));
            });

            $this->info('✅ Письмо успешно записано в лог!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Ошибка: ' . $e->getMessage());
            $this->error('Class: ' . get_class($e));
            \Log::error('TestMail error: ' . $e->getMessage());
            return 1;
        }
    }
}
