<?php

namespace App\Exceptions;

use App\Exceptions\Errors\ErrorCode;

class RateLimitException extends RenderableException
{
    public function __construct(
        string $userMessage = '',
        array $context = [],
        array $debugData = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ErrorCode::RATE_LIMIT_EXCEEDED,
            $userMessage,
            $context,
            $debugData,
            $previous
        );
    }
}