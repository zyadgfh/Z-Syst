<?php

namespace App\Exceptions;

use App\Helpers\Logger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

// Sentry integration (optional - requires sentry/sentry-laravel package)
use Sentry\Laravel\Facades\Sentry;
use Sentry\State\Scope;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Model not found (from findOrFail)
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $model = class_basename($e->getModel());

                return response()->json([
                    'success' => false,
                    'message' => __('errors.resource_not_found', ['resource' => $model]),
                    'error_code' => 'NOT_FOUND_RESOURCE',
                ], 404);
            }
        });

        // Route not found (404)
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.route_not_found'),
                    'error_code' => 'VALIDATION_ROUTE_NOT_FOUND',
                ], 404);
            }
        });

        // Method not allowed
        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.method_not_allowed'),
                    'error_code' => 'VALIDATION_METHOD_NOT_ALLOWED',
                ], 405);
            }
        });

        // Authentication
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.unauthenticated'),
                    'error_code' => 'AUTH_UNAUTHORIZED',
                ], 401);
            }
        });

        // Throttle (rate limit)
        $this->renderable(function (ThrottleRequestsException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.too_many_requests'),
                    'error_code' => 'RATE_LIMIT_EXCEEDED',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
                ], 429);
            }
        });

        // Authorization exception
        $this->renderable(function (\App\Exceptions\AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'AUTH_FORBIDDEN',
                ], 403);
            }
        });
    }

    public function report(Throwable $e): void
    {
        if ($e instanceof RenderableException) {
            $e->report();

            // Report to Sentry if configured
            if (class_exists(\Sentry\Laravel\Facades\Sentry::class) && config('sentry.dsn')) {
                \Sentry\Laravel\Facades\Sentry::captureException($e);
            }

            return;
        }

        // Log all unhandled exceptions to errors channel
        Logger::error($e);

        // Report to Sentry for unhandled exceptions
        if (class_exists(\Sentry\Laravel\Facades\Sentry::class) && config('sentry.dsn')) {
            \Sentry\Laravel\Facades\Sentry::captureException($e);
        }

        parent::report($e);
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            // If it's our custom exception, use its render method
            if ($e instanceof RenderableException) {
                return $e->render($request);
            }

            // Let parent handle renderable callbacks (AuthenticationException, ValidationException, etc.)
            $parentResponse = parent::render($request, $e);
            if ($parentResponse instanceof JsonResponse) {
                return $parentResponse;
            }

            // Fallback for any unhandled exception
            $status = method_exists($e, 'getStatusCode')
                ? $e->getStatusCode()
                : ($e->getCode() > 0 && $e->getCode() < 600 ? $e->getCode() : 500);

            $response = [
                'success' => false,
                'message' => $status === 500
                    ? __('errors.internal_error')
                    : $e->getMessage(),
                'error_code' => $status === 500 ? 'SYSTEM_INTERNAL_ERROR' : 'UNKNOWN_ERROR',
            ];

            if (config('app.debug')) {
                $response['debug'] = [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ];
            }

            // Report to Sentry for 500 errors
            if ($status === 500 && class_exists(\Sentry\Laravel\Facades\Sentry::class) && config('sentry.dsn')) {
                \Sentry\Laravel\Facades\Sentry::captureException($e);
            }

            return response()->json($response, $status);
        }

        return parent::render($request, $e);
    }
}
