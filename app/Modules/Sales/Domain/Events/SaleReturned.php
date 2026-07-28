<?php

declare(strict_types=1);

namespace App\Modules\Sales\Domain\Events;

use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a sale return/refund is created.
 * Stock is restored, financial records are updated.
 */
class SaleReturned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Sale $sale,
        public readonly SaleReturn $saleReturn,
        public readonly array $items,
        public readonly ?string $reason = null,
    ) {}
}

