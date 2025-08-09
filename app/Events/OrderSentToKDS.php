<?php
namespace App\Events;
use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderSentToKDS implements ShouldBroadcast
{
    use SerializesModels;
    public function __construct(public Order $order) {}

    public function broadcastOn() {
        return new PrivateChannel('branch.'.$this->order->branch_id);
    }

    public function broadcastAs() { return 'order.sent_to_kds'; }

    public function broadcastWith() {
        return ['order' => $this->order->load('items.product','table')];
    }
}
