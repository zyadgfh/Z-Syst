<?php

namespace App\Exceptions;

use Exception;

class BusinessValidationException extends Exception
{
    protected $errors;

    public function __construct(array $errors, string $message = 'Business validation failed')
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}