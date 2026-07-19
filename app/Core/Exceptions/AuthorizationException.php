<?php

namespace App\Core\Exceptions;

use Exception;

class AuthorizationException extends ApiException
{
    public function __construct(string $message = 'Authorization failed')
    {
        parent::__construct($message, 403);
    }
}

