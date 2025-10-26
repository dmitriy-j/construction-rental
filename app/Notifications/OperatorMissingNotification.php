<?php

namespace App\Notifications;

use App\Models\Equipment;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OperatorMissingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Equipment $equipment,
        public Order $order
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Требуется оператор для оборудования')
            ->line("Для оборудования {$this->equipment->title} в заказе #{$this->order->id} не назначен оператор.")
            ->action('Назначить оператора', route('lessor.equipment.operators', $this->equipment))
            ->line('Пожалуйста, назначьте оператора как можно скорее.');
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => "Требуется оператор для {$this->equipment->title} (заказ #{$this->order->id})",
            'link' => route('lessor.equipment.operators', $this->equipment),
        ];
    }
}
