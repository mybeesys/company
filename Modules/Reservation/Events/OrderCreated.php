<?php

namespace Modules\Reservation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderCreated implements ShouldBroadcast
{
    use InteractsWithSockets;

    public $orderData;

    public function __construct($orderData)
    {
        $this->orderData = $orderData;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('reservation-channel');
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }
}
