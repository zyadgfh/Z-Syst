<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BusinessRuleException extends Exception
{
    protected string $errorCode;
    protected array $context;

    public function __construct(string $errorCode, string $message, array $context = [])
    {
        $this->errorCode = $errorCode;
        $this->context = $context;
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'context' => config('app.debug') ? $this->context : [],
        ], 422);
    }
}
