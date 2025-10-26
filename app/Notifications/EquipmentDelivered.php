<?php

namespace App\Notifications;

use App\Models\DeliveryNote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EquipmentDelivered extends Notification
{
    use Queueable;

    public $deliveryNote;

    public function __construct(DeliveryNote $deliveryNote)
    {
        $this->deliveryNote = $deliveryNote;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Оборудование доставлено')
            ->line("Оборудование {$this->deliveryNote->orderItem->equipment->title} успешно доставлено")
            ->action('Просмотреть документы', route('documents.show', $this->deliveryNote->id))
            ->line('Благодарим за использование нашего сервиса!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Оборудование доставлено',
            'note_id' => $this->deliveryNote->id,
            'equipment' => $this->deliveryNote->orderItem->equipment->title,
        ];
    }
}
