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

class ProductUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly int $businessId,
        public readonly array $oldData,
        public readonly array $newData
    ) {}
}

class ProductDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly int $businessId,
        public readonly string $productName
    ) {}
}

class StockChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $stockId,
        public readonly int $productId,
        public readonly int $businessId,
        public readonly int $oldQuantity,
        public readonly int $newQuantity,
        public readonly string $changeType // 'increase', 'decrease', 'adjustment'
    ) {}
}