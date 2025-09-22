<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Аренда активирована #{$this->order->id}")
            ->line("Ваш заказ #{$this->order->id} был успешно активирован.");
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => "Заказ #{$this->order->id} активирован",
            'order_id' => $this->order->id,
            'link' => route('lessee.orders.show', $this->order),
        ];
    }
}
