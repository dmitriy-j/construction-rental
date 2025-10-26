<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class OperatorMissing implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Equipment $equipment,
        public Order $order
    ) {}

    public function broadcastOn()
    {
        return new PrivateChannel("company.{$this->order->lessor_company_id}");
    }
}
