<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewUserRegisteredNotification;

class SendNewUserNotificationToAdmin
{
    public function handle(Registered $event)
    {
        $user = $event->user;

        // Отправляем уведомление на офисную почту
        Notification::route('mail', 'office@fap24.ru')
            ->notify(new NewUserRegisteredNotification($user));
    }
}
