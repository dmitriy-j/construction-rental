<?php

namespace App\Listeners;

use App\Events\NewProposalReceived;
use App\Notifications\NewProposalNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendProposalNotification
{
    public function handle(NewProposalReceived $event): void
    {
        $event->proposal->rentalRequest->user->notify(
            new NewProposalNotification($event->proposal)
        );
    }
}
