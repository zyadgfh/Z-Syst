<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedAccessException extends ApiException
{
    public function __construct(
        string $message = 'You do not have permission to perform this action',
        array $errors = []
    ) {
        parent::__construct($message, 403, 'UNAUTHORIZED_ACCESS', $errors);
    }
}
