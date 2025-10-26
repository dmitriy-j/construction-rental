<?php

namespace App\Events;

use App\Models\DeliveryNote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryNoteSigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deliveryNote;

    public function __construct(DeliveryNote $deliveryNote)
    {
        $this->deliveryNote = $deliveryNote;
    }
}
