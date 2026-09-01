<?php

namespace App\Events;

use App\Models\InventoryAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryAlertCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public InventoryAlert $alert
    ) {}

    public function broadcastOn(): array
    {
        $businessId = $this->alert->business_id ?? 'global';

        return [
            new PrivateChannel("inventory-alerts.{$businessId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'alert.created';
    }

    public function broadcastWith(): array
    {
        $product = $this->alert->product;

        return [
            'id'           => $this->alert->id,
            'type'         => $this->alert->type,
            'severity'     => $this->alert->severity,
            'message'      => $this->alert->message,
            'product_name' => $product?->productName ?? '—',
            'product_id'   => $this->alert->product_id,
            'stock'        => $this->alert->current_stock,
            'reorder_qty'  => $this->alert->suggested_reorder_qty,
            'created_at'   => $this->alert->created_at->toISOString(),
        ];
    }
}
