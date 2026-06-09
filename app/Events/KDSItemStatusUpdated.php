<?php
namespace App\Events;

use App\Models\OrderItem;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class KDSItemStatusUpdated implements ShouldBroadcastNow
{
    use SerializesModels;
    public function __construct(public OrderItem $item)
    {
        $this->item->loadMissing(['order.table', 'product', 'modifiers.modifier']);
    }

    public function broadcastOn() {
        return new PrivateChannel('branch.'.$this->item->order->branch_id);
    }

    public function broadcastAs() { return 'kds.item_status_updated'; }

    public function broadcastWith() {
        return [
            'event' => 'kds.item_status_updated',
            'branch_id' => (int) $this->item->order->branch_id,
            'server_time' => now()->toISOString(),
            'item' => $this->item,
        ];
    }
}
