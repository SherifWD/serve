<?php
namespace App\Events;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class OrderReadyForCashier implements ShouldBroadcastNow
{
    use SerializesModels;
    public function __construct(public Order $order)
    {
        $this->order->loadMissing(['table', 'customer', 'items.product', 'payments']);
    }

    public function broadcastOn() {
        return new PrivateChannel('branch.'.$this->order->branch_id);
    }

    public function broadcastAs() { return 'order.ready_for_cashier'; }

    public function broadcastWith() {
        return [
            'event' => 'order.ready_for_cashier',
            'branch_id' => (int) $this->order->branch_id,
            'server_time' => now()->toISOString(),
            'order' => $this->order,
        ];
    }
}
