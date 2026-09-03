<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected $statusCode;
    protected $errorCode;
    protected $errors;

    public function __construct(
        string $message = 'An error occurred',
        int $statusCode = 400,
        string $errorCode = 'API_ERROR',
        array $errors = []
    ) {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'errors' => $this->errors,
        ], $this->statusCode);
    }
}
