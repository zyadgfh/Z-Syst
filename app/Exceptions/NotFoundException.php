<?php

namespace App\Exceptions;

use App\Exceptions\Errors\ErrorCode;

class NotFoundException extends RenderableException
{
    public function __construct(
        string $resource = 'Resource',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ErrorCode::NOT_FOUND_RESOURCE,
            __('errors.resource_not_found', ['resource' => $resource]),
            $context,
            [],
            $previous
        );
    }
}

