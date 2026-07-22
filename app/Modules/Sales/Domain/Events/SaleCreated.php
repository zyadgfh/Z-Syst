<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Events;

use App\Models\Sale;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a new sale is created.
 * Other modules can listen to this event for cross-cutting concerns
 * (e.g., loyalty points, notifications, analytics).
 */
class SaleCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Sale $sale
    ) {}
}

