<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('kitchen'),
            new Channel('order.' . $this->order->id),
        ];
    }

    public function broadcastAs()
    {
        return 'order-cancelled';
    }

    public function broadcastWith()
    {
        return [
            'order_id' => $this->order->id,
            'table_id' => $this->order->table_id,
            'cancelled_at' => now(),
        ];
    }
}
