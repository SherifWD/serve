<?php
namespace App\Events;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderSentToKDS implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function __construct(public Order $order) {
        // ensure items+product are loaded
        $this->order->loadMissing('items.product');
    }

    public function broadcastOn()
    {
        return ['branch.'.$this->order->branch_id.'.kds'];
    }

    public function broadcastAs()
    {
        return 'order.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id'    => $this->order->id,
                'table' => optional($this->order->table)->name,
                'items' => $this->order->items->map(function ($it) {
                    return [
                        'id'         => $it->id,
                        'product'    => ['id' => $it->product_id, 'name' => optional($it->product)->name],
                        'quantity'   => $it->quantity,
                        'kds_status' => $it->kds_status,
                        'item_note'  => $it->item_note,     // ← IMPORTANT
                        'change_note'=> $it->change_note,   // (optional)
                        // include answers/modifiers if your KDS shows them:
                        // 'answers'    => ...,
                        // 'modifiers'  => ...,
                    ];
                })->values(),
            ],
        ];
    }
}