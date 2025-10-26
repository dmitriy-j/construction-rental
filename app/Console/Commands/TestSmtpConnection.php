<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestSmtpConnection extends Command
{
    protected $signature = 'mail:test-connection {email}';

    protected $description = 'Test SMTP connection';

    public function handle()
    {
        $email = $this->argument('email');

        // Вариант 1: Проверка подключения без отправки
        try {
            $transport = Mail::mailer()->getSymfonyTransport();
            $transport->start();
            $this->info('✅ SMTP connection successful!');
            $transport->stop();
        } catch (\Exception $e) {
            $this->error('❌ Connection failed: '.$e->getMessage());
        }

        // Вариант 2: Отправка тестового письма
        $this->info('Sending test email...');
        try {
            Mail::raw('This is a test email', function ($message) use ($email) {
                $message->to($email)->subject('SMTP Test');
            });
            $this->info("✅ Email sent to $email");
        } catch (\Exception $e) {
            $this->error('❌ Send failed: '.$e->getMessage());
        }
    }
}
