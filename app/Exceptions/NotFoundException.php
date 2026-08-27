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
        $vars = ['resource' => $resource];
        $message = __('errors.resource_not_found', $vars);

        // If translation is not available, use default format
        if ($message === 'errors.resource_not_found') {
            $message = $resource . ' not found';
        }

        parent::__construct(
            ErrorCode::RESOURCE_NOT_FOUND,
            $message,
            $context,
            [],
            $previous
        );
    }
}
