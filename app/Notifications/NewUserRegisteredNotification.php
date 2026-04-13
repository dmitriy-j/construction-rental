<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification
{
    use Queueable;

    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Новый пользователь на платформе FAP24')
            ->greeting('Здравствуйте!')
            ->line('На платформе зарегистрировался новый пользователь.')
            ->line('**Имя:** ' . $this->user->name)
            ->line('**Email:** ' . $this->user->email)
            ->line('**Компания:** ' . optional($this->user->company)->legal_name ?? 'Не указана')
            ->line('**Дата регистрации:** ' . $this->user->created_at->format('d.m.Y H:i'))
            ->action('Перейти в админ-панель', url('/admin'))
            ->line('С уважением, команда FAP24.');
    }
}
