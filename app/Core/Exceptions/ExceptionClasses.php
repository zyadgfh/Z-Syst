<?php

namespace App\Core\Exceptions;

use Exception;

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
 * Validation exception
 */
class ValidationException extends BaseException
{
    private array $errors = [];

    public function __construct(array $errors = [], string $message = "Validation failed", Exception $previous = null)
    {
        parent::__construct($message, 422, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * Authorization exception
 */
class AuthorizationException extends BaseException
{
    public function __construct(string $message = "Unauthorized action", Exception $previous = null)
    {
        parent::__construct($message, 403, $previous);
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

