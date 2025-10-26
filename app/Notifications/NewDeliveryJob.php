<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDeliveryJob extends Notification
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $order = $this->order;
        $items = $order->items->map(fn ($item) => "{$item->equipment->title} (x{$item->quantity})")->implode("\n");

        return (new MailMessage)
            ->subject('Новый заказ на доставку оборудования')
            ->line('Вам назначен новый заказ на доставку строительного оборудования.')
            ->line("**Номер заказа:** {$order->id}")
            ->line("**Компания-отправитель:** {$order->lessorCompany->legal_name}")
            ->line("**Компания-получатель:** {$order->lesseeCompany->legal_name}")
            ->line("**Адрес загрузки:** {$order->deliveryFrom->address}")
            ->line("**Адрес доставки:** {$order->deliveryTo->address}")
            ->line("**Оборудование:**\n$items")
            ->line("**Общий вес:** {$order->items->sum('equipment.weight')} кг")
            ->action('Принять заказ', route('carrier.orders.accept', $order))
            ->line('Пожалуйста, подтвердите принятие заказа в течение 2 часов');
    }
}
