<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;

class InventoryMovementService
{
    public function add(
        InventoryItem $item,
        float $quantity,
        string $reason,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $referenceCode = null,
    ): InventoryTransaction {
        return $this->move($item, 'in', $quantity, null, $reason, $sourceType, $sourceId, $referenceCode);
    }

    public function remove(
        InventoryItem $item,
        float $quantity,
        string $reason,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $referenceCode = null,
    ): InventoryTransaction {
        return $this->move($item, 'out', $quantity, null, $reason, $sourceType, $sourceId, $referenceCode);
    }

    public function set(
        InventoryItem $item,
        float $targetQuantity,
        string $reason,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $referenceCode = null,
    ): InventoryTransaction {
        return $this->move($item, 'adjustment', $targetQuantity, $targetQuantity, $reason, $sourceType, $sourceId, $referenceCode);
    }

    private function move(
        InventoryItem $item,
        string $type,
        float $quantity,
        ?float $targetQuantity,
        string $reason,
        ?string $sourceType,
        ?int $sourceId,
        ?string $referenceCode,
    ): InventoryTransaction {
        $before = round((float) $item->quantity, 3);
        $after = match ($type) {
            'in' => $before + $quantity,
            'out' => max(0, $before - $quantity),
            'adjustment' => max(0, (float) $targetQuantity),
            default => $before,
        };

        $item->quantity = round($after, 3);
        $item->save();

        return InventoryTransaction::query()->create([
            'inventory_item_id' => $item->id,
            'type' => $type,
            'quantity' => round($quantity, 3),
            'balance_before' => $before,
            'balance_after' => round($after, 3),
            'reason' => $reason,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'reference_code' => $referenceCode,
        ]);
    }
}
