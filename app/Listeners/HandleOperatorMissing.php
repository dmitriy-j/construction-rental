<?php

namespace App\Listeners;

use App\Events\OperatorMissing;
use App\Notifications\OperatorMissingNotification;

class HandleOperatorMissing
{
    public function handle(OperatorMissing $event)
    {
        $managers = $event->order->lessorCompany->users()
            ->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
            ->get();

        Notification::send($managers, new OperatorMissingNotification(
            $event->equipment,
            $event->order
        ));
    }
}
