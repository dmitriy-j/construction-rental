<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDocumentAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    protected $document;

    protected $type;

    public function __construct($document, $type)
    {
        $this->document = $document;
        $this->type = $type;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Новый {$this->type} доступен для заказа #{$this->document->order_id}")
            ->line("Для вашего заказа #{$this->document->order_id} доступен новый {$this->type}.")
            ->action('Просмотреть документы', url('/lessee/documents'))
            ->line('Спасибо за использование нашей платформы!');
    }

    public function toArray($notifiable)
    {
        return [
            'document_id' => $this->document->id,
            'document_type' => $this->type,
            'order_id' => $this->document->order_id,
            'message' => "Доступен новый {$this->type} для заказа #{$this->document->order_id}",
        ];
    }
}
