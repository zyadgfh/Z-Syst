<?php

namespace App\Exceptions;

use Exception;

class ProductNotFoundException extends Exception
{
    protected $productId;

    public function __construct($productId)
    {
        $this->productId = $productId;
        parent::__construct("Product with ID {$productId} not found.");
    }

    public function getProductId()
    {
        return $this->productId;
    }
}