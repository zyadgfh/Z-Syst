<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BranchLimitExceededException extends Exception
{
    public function __construct(
        string $message = 'You have reached the maximum number of branches allowed by your subscription.',
        int $statusCode = 403,
        protected ?array $context = null
    ) {
        parent::__construct($message, $statusCode);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'data' => $this->context,
        ], $this->getCode());
    }
}
