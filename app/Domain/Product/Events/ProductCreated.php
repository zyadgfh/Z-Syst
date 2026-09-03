<?php

namespace App\Domain\Product\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly int $businessId,
        public readonly string $productName,
        public readonly array $data
    ) {}
}
