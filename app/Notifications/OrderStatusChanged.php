<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Order; // Добавляем импорт модели

class OrderStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public Order $order) // Исправляем тип параметра
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'message' => "Статус заказа #{$this->order->id} изменен: " . $this->order->status_text,
            'url' => route('orders.show', $this->order),
        ];
    }
}
