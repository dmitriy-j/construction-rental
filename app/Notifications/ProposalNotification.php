<?php
// app/Notifications/ProposalNotification.php
namespace App\Notifications;

use App\Models\RentalRequestResponse;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProposalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $proposal;
    public $type; // 'new_proposal', 'proposal_accepted', 'proposal_rejected', 'counter_offer'

    public function __construct(RentalRequestResponse $proposal, string $type = 'new_proposal')
    {
        $this->proposal = $proposal;
        $this->type = $type;
    }

    public function via($notifiable): array
    {
        $channels = ['database'];

        // Email только если пользователь включил email-уведомления
        try {
            if (method_exists($notifiable, 'notificationSettings') && $notifiable->notificationSettings) {
                $settings = $notifiable->notificationSettings;
                if ($settings->email_notifications ?? true) {
                    $channels[] = 'mail';
                }
            } else {
                // Если настройки не найдены, отправляем email по умолчанию
                $channels[] = 'mail';
            }
        } catch (\Exception $e) {
            // При ошибке отправляем email по умолчанию
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $rentalRequest = $this->proposal->rentalRequest;
        $equipment = $this->proposal->equipment;

        $subject = match ($this->type) {
            'new_proposal' => "Новое предложение по заявке «{$rentalRequest->title}»",
            'proposal_accepted' => "Ваше предложение по «{$rentalRequest->title}» принято!",
            'proposal_rejected' => "Предложение по «{$rentalRequest->title}» отклонено",
            'counter_offer' => "Контрпредложение по заявке «{$rentalRequest->title}»",
            default => "Обновление по заявке «{$rentalRequest->title}»",
        };

        $greeting = match ($this->type) {
            'new_proposal' => 'Здравствуйте! По вашей заявке получено новое предложение.',
            'proposal_accepted' => 'Поздравляем! Ваше предложение было принято.',
            'proposal_rejected' => 'Ваше предложение было отклонено арендатором.',
            'counter_offer' => 'Арендатор сделал контрпредложение.',
            default => 'Обновление статуса по вашей заявке.',
        };

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting);

        if ($equipment) {
            $mailMessage->line("Техника: {$equipment->title}")
                ->line("Цена: " . number_format($this->proposal->proposed_price, 2, ',', ' ') . " ₽")
                ->line("Количество: {$this->proposal->proposed_quantity} ед.");
        }

        if ($this->proposal->message) {
            $mailMessage->line("Комментарий: {$this->proposal->message}");
        }

        $url = match ($this->type) {
            'new_proposal', 'counter_offer' => url("/requests/{$rentalRequest->id}"),
            'proposal_accepted', 'proposal_rejected' => url("/lessor/rental-requests/{$rentalRequest->id}"),
            default => url("/requests"),
        };

        $mailMessage->action('Перейти к заявке', $url)
            ->line('Спасибо, что пользуетесь нашей платформой!');

        return $mailMessage;
    }

    public function toDatabase($notifiable): array
    {
        $rentalRequest = $this->proposal->rentalRequest;
        $equipment = $this->proposal->equipment;

        $title = match ($this->type) {
            'new_proposal' => "Новое предложение по заявке",
            'proposal_accepted' => "Предложение принято",
            'proposal_rejected' => "Предложение отклонено",
            'counter_offer' => "Контрпредложение",
            default => "Обновление заявки",
        };

        $body = match ($this->type) {
            'new_proposal' => "По заявке «{$rentalRequest->title}» получено новое предложение" .
                ($equipment ? " от {$equipment->title}" : "") .
                " на сумму " . number_format($this->proposal->proposed_price, 2, ',', ' ') . " ₽",
            'proposal_accepted' => "Ваше предложение по заявке «{$rentalRequest->title}» принято!" .
                " Сумма: " . number_format($this->proposal->proposed_price, 2, ',', ' ') . " ₽",
            'proposal_rejected' => "Ваше предложение по заявке «{$rentalRequest->title}» отклонено.",
            'counter_offer' => "По заявке «{$rentalRequest->title}» получено контрпредложение.",
            default => "Статус заявки «{$rentalRequest->title}» изменён.",
        };

        return [
            'type' => $this->type,
            'title' => $title,
            'body' => $body,
            'proposal_id' => $this->proposal->id,
            'rental_request_id' => $rentalRequest->id,
            'rental_request_title' => $rentalRequest->title,
            'proposed_price' => $this->proposal->proposed_price,
            'equipment_title' => $equipment ? $equipment->title : null,
            'action_url' => match ($this->type) {
                'new_proposal', 'counter_offer' => url("/requests/{$rentalRequest->id}"),
                'proposal_accepted', 'proposal_rejected' => url("/lessor/rental-requests/{$rentalRequest->id}"),
                default => url("/requests"),
            },
        ];
    }
}
