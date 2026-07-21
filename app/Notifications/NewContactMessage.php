<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewContactMessage extends Notification
{
    use Queueable;

    public ContactMessage $contactMessage;

    public function __construct(ContactMessage $contactMessage)
    {
        $this->contactMessage = $contactMessage;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contact_message',
            'contact_message_id' => $this->contactMessage->id,
            'name' => $this->contactMessage->name,
            'phone' => $this->contactMessage->phone,
            'email' => $this->contactMessage->email,
            'message' => $this->contactMessage->message,
            'created_at' => $this->contactMessage->created_at->toDateTimeString(),
            'url' => route('admin.contacts.index'),
        ];
    }
}
