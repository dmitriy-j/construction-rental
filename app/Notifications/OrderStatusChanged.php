<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification; // Добавляем импорт модели

class OrderStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public Order $order) // Исправляем тип параметра
    {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'message' => "Статус заказа #{$this->order->id} изменен: ".$this->order->status_text,
            'url' => route('orders.show', $this->order),
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'message' => "Статус заказа #{$this->order->id} изменен: ".Order::statusText($this->order->status),
            'url' => route('lessee.orders.show', $this->order),
        ];
    }
}
