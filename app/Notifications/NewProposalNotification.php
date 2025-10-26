<?php

namespace App\Notifications;

use App\Models\RentalRequestResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewProposalNotification extends Notification
{
    use Queueable;

    public function __construct(public RentalRequestResponse $proposal) {}

    public function toArray($notifiable): array
    {
        return [
            'message' => 'Новое предложение по вашей заявке',
            'request_id' => $this->proposal->rental_request_id,
            'lessor_name' => $this->proposal->lessor->company->name,
            'proposal_price' => $this->proposal->proposed_price,
        ];
    }
}
