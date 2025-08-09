<?php
namespace App\Events;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderSentToKDS implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order->loadMissing('items.product');
    }

    public function broadcastOn()
    {
        return ['branch.'.$this->order->branch_id.'.kds'];
    }

    public function broadcastAs()
    {
        return 'order.sent';
    }
}