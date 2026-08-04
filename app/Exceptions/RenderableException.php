<?php

namespace App\Exceptions;

use App\Exceptions\Errors\ErrorCode;
use Illuminate\Support\Facades\Log;

abstract class RenderableException extends \Exception
{
    public ErrorCode $errorCode;
    public array $context;
    public array $debugData;

    public function __construct(
        ErrorCode $errorCode,
        string $userMessage = '',
        array $context = [],
        array $debugData = [],
        ?\Throwable $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->context = $context;
        $this->debugData = $debugData;

        $message = $userMessage ?: __('errors.' . $errorCode->value);
        parent::__construct($message, $errorCode->httpStatus(), $previous);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode->value,
        ];

        // Add validation errors if present
        if (!empty($this->context['errors'])) {
            $response['errors'] = $this->context['errors'];
        }

        // Add debug data in non-production environments
        if (config('app.debug')) {
            $response['debug'] = array_merge([
                'exception' => get_class($this),
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => $this->getTraceAsString(),
            ], $this->debugData);
        }

        return response()->json($response, $this->errorCode->httpStatus());
    }

    /**
     * Log the exception with context.
     */
    public function report(): void
    {
        $logData = [
            'error_code' => $this->errorCode->value,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'debug' => $this->debugData,
        ];

        $logLevel = $this->errorCode->logLevel();

        Log::channel('errors')->log($logLevel, $this->getMessage(), $logData);

        if ($this->errorCode->shouldAlert()) {
            $this->sendAlert($logData);
        }

        // Also log critical errors to critical channel
        if ($logLevel === 'critical') {
            Log::channel('critical')->critical($this->getMessage(), $logData);
        }
    }

    protected function sendAlert(array $logData): void
    {
        // No external alert integration configured yet.
    }
}

