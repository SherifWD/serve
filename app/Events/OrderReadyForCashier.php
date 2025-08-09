<?php
namespace App\Events;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderReadyForCashier implements ShouldBroadcast
{
    use SerializesModels;
    public function __construct(public Order $order) {}

    public function broadcastOn() {
        return new PrivateChannel('branch.'.$this->order->branch_id);
    }

    public function broadcastAs() { return 'order.ready_for_cashier'; }

    public function broadcastWith() {
        return ['order' => $this->order->load('items','table')];
    }
}

