<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderApproved extends Notification
{
    use Queueable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Заказ подтвержден')
            ->line("Ваш заказ #{$this->order->id} был подтвержден арендодателем.")
            ->action('Просмотреть заказ', route('lessee.orders.show', $this->order))
            ->line('Спасибо за использование нашей платформы!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Заказ #{$this->order->id} подтвержден арендодателем",
            'order_id' => $this->order->id,
            'link' => route('lessee.orders.show', $this->order),
        ];
    }
}
