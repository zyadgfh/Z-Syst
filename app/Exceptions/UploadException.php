<?php

namespace App\Exceptions;

use App\Exceptions\Errors\ErrorCode;

class UploadException extends RenderableException
{
    public function __construct(
        string $reason = '',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ErrorCode::UPLOAD_STORAGE_FAILED,
            __('errors.upload_failed', ['reason' => $reason]),
            $context,
            [],
            $previous
        );
    }
}
