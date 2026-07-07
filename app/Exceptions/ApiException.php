<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected int $statusCode;

    protected mixed $errors;

    public function __construct(string $message = 'Application error', int $statusCode = 400, mixed $errors = null)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): mixed
    {
        return $this->errors;
    }

    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => __($this->getMessage()),
        ];

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->statusCode);
    }

    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self($message, 403);
    }

    public static function validationError(mixed $errors, string $message = 'Validation failed'): self
    {
        return new self($message, 422, $errors);
    }

    public static function badRequest(string $message = 'Bad request'): self
    {
        return new self($message, 400);
    }

    public static function conflict(string $message = 'Resource conflict'): self
    {
        return new self($message, 409);
    }

    public static function tooManyRequests(string $message = 'Too many requests'): self
    {
        return new self($message, 429);
    }
}