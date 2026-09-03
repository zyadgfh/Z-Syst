<?php

namespace App\Exceptions;

use App\Exceptions\Errors\ErrorCode;

class InvalidInputException extends RenderableException
{
    public function __construct(
        string $userMessage = '',
        array $context = [],
        array $debugData = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ErrorCode::VALIDATION_FAILED,
            $userMessage,
            $context,
            $debugData,
            $previous
        );
    }
}