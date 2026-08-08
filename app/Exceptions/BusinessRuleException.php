<?php

namespace App\Exceptions;

use App\Exceptions\Errors\ErrorCode;

class BusinessRuleException extends RenderableException
{
    public function __construct(
        ErrorCode $errorCode = ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
        string $userMessage = '',
        array $context = [],
        array $debugData = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($errorCode, $userMessage, $context, $debugData, $previous);
    }
}
