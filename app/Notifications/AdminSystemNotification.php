<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminSystemNotification extends Notification
{
    use Queueable;

    public string $type;
    public string $title;
    public array $data;
    public string $actionUrl;

    public function __construct(string $type, string $title, array $data, string $actionUrl = '')
    {
        $this->type = $type;
        $this->title = $title;
        $this->data = $data;
        $this->actionUrl = $actionUrl;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'data' => $this->data,
            'action_url' => $this->actionUrl,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
