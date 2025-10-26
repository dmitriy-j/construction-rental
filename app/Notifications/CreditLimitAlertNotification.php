<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $amount;

    protected $invoice;

    public function __construct($amount, $invoice = null)
    {
        $this->amount = $amount;
        $this->invoice = $invoice;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Получен новый платеж')
            ->line('На ваш счет поступил платеж на сумму '.number_format($this->amount, 2).' ₽')
            ->action('Перейти к балансу', url('/balance'))
            ->line('Спасибо за использование нашей платформы!');
    }

    public function toArray($notifiable)
    {
        return [
            'amount' => $this->amount,
            'invoice_id' => $this->invoice ? $this->invoice->id : null,
            'message' => 'Получен платеж на сумму '.number_format($this->amount, 2).' ₽',
        ];
    }
}
