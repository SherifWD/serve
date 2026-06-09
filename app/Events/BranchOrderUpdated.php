<?php

namespace App\Events;

use App\Models\Order;
use DateTimeInterface;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class BranchOrderUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public Order $order,
        public string $event = 'order.updated',
    ) {
        $this->order->loadMissing(['table', 'customer', 'items.product', 'payments']);
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('branch.'.$this->order->branch_id);
    }

    public function broadcastAs(): string
    {
        return $this->event;
    }

    public function broadcastWith(): array
    {
        return [
            'event' => $this->event,
            'branch_id' => (int) $this->order->branch_id,
            'server_time' => now()->toISOString(),
            'order' => [
                'id' => (int) $this->order->id,
                'table_id' => $this->order->table_id ? (int) $this->order->table_id : null,
                'table_name' => $this->order->table?->name,
                'customer_name' => $this->order->customer?->name,
                'order_type' => $this->order->order_type,
                'status' => $this->order->status,
                'payment_status' => $this->order->payment_status,
                'subtotal' => round((float) $this->order->subtotal, 2),
                'tax' => round((float) $this->order->tax, 2),
                'discount' => round((float) $this->order->discount, 2),
                'total' => round((float) $this->order->total, 2),
                'paid_amount' => round((float) $this->order->payments->sum('amount'), 2),
                'items_count' => (int) $this->order->items->sum('quantity'),
                'kds_sent_at' => $this->isoTimestamp($this->order->kds_sent_at),
                'paid_at' => $this->isoTimestamp($this->order->paid_at),
                'updated_at' => $this->isoTimestamp($this->order->updated_at),
            ],
        ];
    }

    private function isoTimestamp(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toISOString();
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toISOString();
        }

        return Carbon::parse($value)->toISOString();
    }
}
