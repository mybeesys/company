<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
// use Illuminate\Broadcasting\InteractsWithSockets;
// use Illuminate\Broadcasting\PresenceChannel;
// use Illuminate\Broadcasting\PrivateChannel;
// use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
// use Illuminate\Foundation\Events\Dispatchable;
// use Illuminate\Queue\SerializesModels;

// class OrderCreated
// {
//     use Dispatchable, InteractsWithSockets, SerializesModels;

//     public $order;

//     public function __construct($order)
//     {
//         $this->order = $order->load('items');
//     }

//     public function broadcastOn()
//     {
//         return new Channel('cashier-orders');
//     }

//     public function broadcastAs()
//     {
//         return 'order.created';
//     }
// }

// use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderCreated implements ShouldBroadcast
{
    public $orderData;

    public function __construct($orderData)
    {
        $this->orderData = $orderData;
    }

    public function broadcastOn()
    {
        return new Channel('reservation-channel');
    }
}
