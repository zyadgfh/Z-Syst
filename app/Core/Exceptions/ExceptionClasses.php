<?php

namespace App\Core\Exceptions;

use Exception;

/**
 * Bundle file kept for backward compatibility with code that imports
 * the legacy exception hierarchy (BaseException / NotFoundException /
 * BusinessException). The domain-specific exceptions (ApiException,
 * AuthenticationException, AuthorizationException, ValidationException,
 * TenantException, BranchLimitExceededException) live in their own
 * files under this namespace and extend ApiException.
 *
 * If you're adding a new domain exception, create a standalone file
 * matching PSR-4 — do NOT append a new class here.
 */

/**
 * Base Exception Class
 */
class BaseException extends Exception
{
    protected $statusCode = 500;

    public function __construct(string $message = "", int $statusCode = 500, Exception $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

/**
 * Resource not found exception
 */
class NotFoundException extends BaseException
{
    public function __construct(string $message = "Resource not found", Exception $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}

/**
 * Business logic exception
 */
class BusinessException extends BaseException
{
    public function __construct(string $message = "Business operation failed", int $statusCode = 400, Exception $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}
