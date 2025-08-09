<?php
namespace App\Events;

use App\Models\OrderItem;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class KDSItemStatusUpdated implements ShouldBroadcast
{
    use SerializesModels;
    public function __construct(public OrderItem $item) {}

    public function broadcastOn() {
        return new PrivateChannel('branch.'.$this->item->order->branch_id);
    }

    public function broadcastAs() { return 'kds.item_status_updated'; }

    public function broadcastWith() {
        return ['item' => $this->item->load('product','order.table')];
    }
}

