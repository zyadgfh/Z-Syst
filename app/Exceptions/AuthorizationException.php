<?php

namespace App\Exceptions;

use App\Exceptions\Errors\ErrorCode;

class AuthorizationException extends RenderableException
{
    public function __construct(
        string $userMessage = '',
        array $context = [],
        array $debugData = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ErrorCode::AUTH_FORBIDDEN,
            $userMessage,
            $context,
            $debugData,
            $previous
        );
    }
}