<?php

namespace App\Core\Exceptions;

use Exception;

class AuthenticationException extends ApiException
{
    public function __construct(string $message = 'Authentication failed')
    {
        parent::__construct($message, 401);
    }
}

