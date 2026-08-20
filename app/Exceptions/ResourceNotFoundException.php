<?php

namespace App\Exceptions;

use Exception;

class ResourceNotFoundException extends ApiException
{
    public function __construct(
        string $resource = 'Resource',
        string $message = null,
        array $errors = []
    ) {
        $message = $message ?? __('The requested :resource was not found.', ['resource' => $resource]);
        parent::__construct($message, 404, 'RESOURCE_NOT_FOUND', $errors);
    }
}
