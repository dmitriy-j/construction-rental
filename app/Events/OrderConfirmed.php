<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderConfirmed
{
    use Dispatchable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
