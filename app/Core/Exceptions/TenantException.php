<?php

namespace App\Core\Exceptions;

use Exception;

class TenantException extends ApiException
{
    public function __construct(string $message = 'Tenant error')
    {
        parent::__construct($message, 404);
    }
}

