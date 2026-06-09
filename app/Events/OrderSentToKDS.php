<?php
namespace App\Events;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class OrderSentToKDS implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order->loadMissing(['table', 'customer', 'items.product', 'items.modifiers.modifier']);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('branch.'.$this->order->branch_id);
    }

    public function broadcastAs()
    {
        return 'order.sent_to_kds';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'order.sent_to_kds',
            'branch_id' => (int) $this->order->branch_id,
            'server_time' => now()->toISOString(),
            'order' => $this->order,
        ];
    }
}
