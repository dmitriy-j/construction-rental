<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderActivatedAdminNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via($notifiable): array
    {
        return ['database']; // Только в базу данных
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => "Заказ #{$this->order->id} активирован",
            'order_id' => $this->order->id,
            'lessee' => $this->order->lesseeCompany->legal_name,
            'lessor' => $this->order->lessorCompany->legal_name,
            'link' => route('admin.orders.show', $this->order),
        ];
    }
}
