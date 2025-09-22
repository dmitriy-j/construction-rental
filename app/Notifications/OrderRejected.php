<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRejected extends Notification
{
    use Queueable;

    public $order;

    public $reason;

    public function __construct(Order $order, string $reason)
    {
        $this->order = $order;
        $this->reason = $reason;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Заказ отклонен')
            ->line("Ваш заказ #{$this->order->id} был отклонен арендодателем.")
            ->line("Причина: {$this->reason}")
            ->action('Просмотреть заказ', route('lessee.orders.show', $this->order))
            ->line('Вы можете изменить условия заказа и попробовать снова.');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Заказ #{$this->order->id} отклонен: {$this->reason}",
            'order_id' => $this->order->id,
            'link' => route('lessee.orders.show', $this->order),
        ];
    }
}
