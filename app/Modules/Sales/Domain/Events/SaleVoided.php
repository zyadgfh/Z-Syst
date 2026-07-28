<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Events;

use App\Models\Sale;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a sale is voided/cancelled.
 * Stock is restored, financial records are reversed.
 */
class SaleVoided
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Sale $sale,
        public readonly ?string $reason = null,
    ) {}
}

