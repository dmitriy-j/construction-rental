<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TestEmail extends Command
{
    protected $signature = 'email:test {email?}';
    protected $description = 'Тестирование отправки email верификации';

    public function handle()
    {
        $email = $this->argument('email');

        if ($email) {
            $user = User::where('email', $email)->first();
        } else {
            $user = User::first();
        }

        if (!$user) {
            $this->error('Пользователь не найден');
            return 1;
        }

        $this->info("Отправка тестового письма на: " . $user->email);

        try {
            $user->sendEmailVerificationNotification();
            $this->info("✅ Письмо успешно отправлено!");
            Log::channel('email')->info('Тестовое письмо отправлено', ['email' => $user->email]);
        } catch (\Exception $e) {
            $this->error("❌ Ошибка отправки: " . $e->getMessage());
            Log::channel('email')->error('Ошибка отправки тестового письма', [
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }

        return 0;
    }
}
