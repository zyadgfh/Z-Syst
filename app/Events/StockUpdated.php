<?php

namespace App\Events;

use App\Models\StockMovement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The stock movement that triggered this update.
     */
    public StockMovement $movement;

    /**
     * The product ID affected.
     */
    public int $productId;

    /**
     * The business ID for channel scoping.
     */
    public int $businessId;

    /**
     * New total stock for the product.
     */
    public int $newTotalStock;

    /**
     * The batch info.
     */
    public ?string $batchNo;

    /**
     * Movement summary for display.
     */
    public array $summary;

    public function __construct(StockMovement $movement, int $newTotalStock)
    {
        $this->movement = $movement;
        $this->productId = $movement->product_id;
        $this->businessId = $movement->business_id;
        $this->newTotalStock = $newTotalStock;
        $this->batchNo = $movement->batch_no;

        $this->summary = [
            'type' => $movement->movement_type,
            'quantity' => $movement->quantity,
            'before' => $movement->before_quantity,
            'after' => $movement->after_quantity,
            'user' => $movement->user->name ?? 'System',
            'notes' => $movement->notes,
            'timestamp' => $movement->created_at->toISOString(),
        ];
    }

    /**
     * The channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("item.{$this->productId}.stock"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'stock.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->productId,
            'new_total_stock' => $this->newTotalStock,
            'batch_no' => $this->batchNo,
            'movement' => $this->summary,
        ];
    }
}
