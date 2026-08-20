<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends ApiException
{
    public function __construct(
        string $message = 'Insufficient stock available',
        array $errors = []
    ) {
        parent::__construct($message, 422, 'INSUFFICIENT_STOCK', $errors);
    }
}
