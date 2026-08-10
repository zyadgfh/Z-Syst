<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    protected $productId;
    protected $requestedQuantity;
    protected $availableQuantity;

    public function __construct($productId, $requestedQuantity, $availableQuantity)
    {
        $this->productId = $productId;
        $this->requestedQuantity = $requestedQuantity;
        $this->availableQuantity = $availableQuantity;

        parent::__construct(
            "Insufficient stock for product ID {$productId}. Requested: {$requestedQuantity}, Available: {$availableQuantity}"
        );
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function getRequestedQuantity()
    {
        return $this->requestedQuantity;
    }

    public function getAvailableQuantity()
    {
        return $this->availableQuantity;
    }
}