<?php

namespace App\Exceptions;

use App\Exceptions\Errors\ErrorCode;

class TransactionException extends RenderableException
{
    public function __construct(
        string $operation = '',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ErrorCode::SYSTEM_TRANSACTION_FAILED,
            __('errors.transaction_failed'),
            array_merge(['operation' => $operation], $context),
            ['previous_exception' => $previous ? $previous->getMessage() : null],
            $previous
        );
    }
}

