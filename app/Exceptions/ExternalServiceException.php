<?php

namespace App\Exceptions;

use App\Exceptions\Errors\ErrorCode;

class ExternalServiceException extends RenderableException
{
    public function __construct(
        string $service = '',
        string $userMessage = '',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ErrorCode::EXTERNAL_MAIL_FAILED,
            $userMessage ?: __('errors.service_unavailable', ['service' => $service]),
            array_merge(['service' => $service], $context),
            ['previous_exception' => $previous ? $previous->getMessage() : null],
            $previous
        );
    }
}
