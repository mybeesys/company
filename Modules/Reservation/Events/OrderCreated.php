<?php

namespace Modules\Reservation\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use SerializesModels;

    public $orderData;

    public function __construct($orderData)
    {
        $this->orderData = $orderData;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('reservation-channel');
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }
}