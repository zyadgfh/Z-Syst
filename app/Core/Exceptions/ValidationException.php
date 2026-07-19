<?php

namespace App\Core\Exceptions;

use Exception;

class ValidationException extends ApiException
{
    public function __construct(string $message = 'Validation failed', $errors = null)
    {
        parent::__construct($message, 422, $errors);
    }
}

