<?php

namespace App\Exceptions;

use App\Exceptions\Errors\ErrorCode;

class DuplicateEntryException extends BusinessRuleException
{
    public function __construct(
        string $field = '',
        string $value = '',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ErrorCode::BUSINESS_DUPLICATE_ENTRY,
            __('errors.duplicate_entry', ['field' => $field, 'value' => $value]),
            $context,
            [],
            $previous
        );
    }
}

