<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // Убрали прямое объявление свойства $queue, так как оно уже есть в Queueable
    // Вместо этого установим очередь в конструкторе

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        // Устанавливаем очередь через метод трейта Queueable
        $this->onQueue('high');
    }

    public function via($notifiable)
    {
        Log::channel('email')->info('Подготовка email верификации', [
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
            'queue' => 'high' // Указываем напрямую для логов
        ]);

        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        Log::channel('email')->debug('Сгенерирован URL верификации', [
            'user_id' => $notifiable->id,
            'url' => $verificationUrl
        ]);

        return (new MailMessage)
            ->subject('Подтверждение email адреса - ФАП')
            ->view('emails.verify-email', [
                'user' => $notifiable,
                'verificationUrl' => $verificationUrl
            ]);
    }

    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    public function failed(\Exception $e)
    {
        Log::channel('email')->error('Ошибка отправки email верификации', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
