<?php

namespace App\Exceptions;

use Exception;

class StockNotFoundException extends Exception
{
    protected $stockId;

    public function __construct($stockId)
    {
        $this->stockId = $stockId;
        parent::__construct("Stock record with ID {$stockId} not found.");
    }

    public function getStockId()
    {
        return $this->stockId;
    }
}