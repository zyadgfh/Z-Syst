<?php

namespace App\Exceptions;

use Exception;

class StockUnavailableException extends Exception
{
    public function __construct(string $message = "الكمية المطلوبة غير متوفرة في المخزون", int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
