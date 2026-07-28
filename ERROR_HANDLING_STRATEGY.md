# Error Handling Strategy - Pharmacy Management System

> **Application**: Laravel 10 Web App + REST API (Pharmacy Management)  
> **Language/Framework**: PHP 8.1+ / Laravel 10  
> **API Clients**: Flutter mobile app, Vue.js/Blade admin panel

---

## 1. Error Classification Scheme

### Severity Levels

| Level | Code | Description | Examples |
|-------|------|-------------|----------|
| **CRITICAL** | 1 | System-level failures requiring immediate attention | DB connection lost, storage full, uncaught exceptions |
| **ERROR** | 2 | Operation failures that prevent a feature from working | Transaction rollback, external API failure, file upload failure |
| **WARNING** | 3 | Non-blocking issues that indicate potential problems | Low stock, expired token, deprecated usage |
| **INFO** | 4 | Informational events for auditing/tracking | User login, record deletion, status changes |

### Error Categories

| Category | HTTP Status | Log Level | Alert |
|----------|-------------|-----------|-------|
| **VALIDATION** | 422 | INFO | No |
| **AUTHENTICATION** | 401 | WARNING | Yes (on repeated failures) |
| **AUTHORIZATION** | 403 | WARNING | Yes |
| **NOT_FOUND** | 404 | INFO | No |
| **BUSINESS_RULE** | 409 / 422 | WARNING | No |
| **CONFLICT** (duplicate) | 409 | INFO | No |
| **RATE_LIMIT** | 429 | WARNING | Yes |
| **TRANSACTION_ERROR** | 500 | ERROR | Yes |
| **EXTERNAL_SERVICE** | 502 / 503 | ERROR | Yes |
| **UPLOAD_ERROR** | 422 | ERROR | No |
| **INTERNAL_ERROR** | 500 | CRITICAL | Yes |

---

## 2. Custom Error Classes

### Directory Structure

```
app/Exceptions/
├── Handler.php
├── RenderableException.php (Interface)
├── ValidationException.php
├── AuthenticationException.php
├── NotFoundException.php
├── BusinessRuleException.php
├── TransactionException.php
├── ExternalServiceException.php
├── UploadException.php
├── DuplicateEntryException.php
└── errors/
    └── ErrorCode.php (Enum)
```

### Error Codes Enum

```php
<?php

namespace App\Exceptions\Errors;

enum ErrorCode: string
{
    // Validation (VALIDATION_*)
    case VALIDATION_FAILED = 'VALIDATION_FAILED';
    case VALIDATION_INVALID_INPUT = 'VALIDATION_INVALID_INPUT';
    case VALIDATION_MISSING_FIELD = 'VALIDATION_MISSING_FIELD';
    case VALIDATION_FILE_TOO_LARGE = 'VALIDATION_FILE_TOO_LARGE';
    case VALIDATION_INVALID_MIME = 'VALIDATION_INVALID_MIME';

    // Authentication (AUTH_*)
    case AUTH_INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS';
    case AUTH_TOKEN_EXPIRED = 'AUTH_TOKEN_EXPIRED';
    case AUTH_TOKEN_INVALID = 'AUTH_TOKEN_INVALID';
    case AUTH_UNAUTHORIZED = 'AUTH_UNAUTHORIZED';
    case AUTH_OTP_EXPIRED = 'AUTH_OTP_EXPIRED';
    case AUTH_OTP_INVALID = 'AUTH_OTP_INVALID';
    case AUTH_EMAIL_NOT_VERIFIED = 'AUTH_EMAIL_NOT_VERIFIED';

    // Authorization (FORBIDDEN_*)
    case FORBIDDEN_ROLE = 'FORBIDDEN_ROLE';
    case FORBIDDEN_PERMISSION = 'FORBIDDEN_PERMISSION';

    // Business Rules (BUSINESS_*)
    case BUSINESS_INSUFFICIENT_STOCK = 'BUSINESS_INSUFFICIENT_STOCK';
    case BUSINESS_DUE_SALE_WALKING_CUSTOMER = 'BUSINESS_DUE_SALE_WALKING_CUSTOMER';
    case BUSINESS_PRESCRIPTION_ALREADY_USED = 'BUSINESS_PRESCRIPTION_ALREADY_USED';
    case BUSINESS_SALE_CANNOT_BE_DELETED = 'BUSINESS_SALE_CANNOT_BE_DELETED';
    case BUSINESS_DUPLICATE_ENTRY = 'BUSINESS_DUPLICATE_ENTRY';
    case BUSINESS_EMAIL_EXISTS = 'BUSINESS_EMAIL_EXISTS';
    case BUSINESS_LOGIN_ROLE_DENIED = 'BUSINESS_LOGIN_ROLE_DENIED';

    // Not Found (NOT_FOUND_*)
    case NOT_FOUND_RESOURCE = 'NOT_FOUND_RESOURCE';
    case NOT_FOUND_RELATION = 'NOT_FOUND_RELATION';

    // External Services (EXTERNAL_*)
    case EXTERNAL_MAIL_UNCONFIGURED = 'EXTERNAL_MAIL_UNCONFIGURED';
    case EXTERNAL_MAIL_FAILED = 'EXTERNAL_MAIL_FAILED';
    case EXTERNAL_SMS_FAILED = 'EXTERNAL_SMS_FAILED';
    case EXTERNAL_PAYMENT_FAILED = 'EXTERNAL_PAYMENT_FAILED';

    // Upload (UPLOAD_*)
    case UPLOAD_STORAGE_FAILED = 'UPLOAD_STORAGE_FAILED';
    case UPLOAD_INVALID_FILE = 'UPLOAD_INVALID_FILE';

    // System (SYSTEM_*)
    case SYSTEM_TRANSACTION_FAILED = 'SYSTEM_TRANSACTION_FAILED';
    case SYSTEM_INTERNAL_ERROR = 'SYSTEM_INTERNAL_ERROR';
    case SYSTEM_MAINTENANCE_MODE = 'SYSTEM_MAINTENANCE_MODE';

    public function httpStatus(): int
    {
        return match($this) {
            self::VALIDATION_FAILED,
            self::VALIDATION_INVALID_INPUT,
            self::VALIDATION_MISSING_FIELD,
            self::VALIDATION_FILE_TOO_LARGE,
            self::VALIDATION_INVALID_MIME,
            self::BUSINESS_INSUFFICIENT_STOCK,
            self::BUSINESS_DUE_SALE_WALKING_CUSTOMER,
            self::BUSINESS_PRESCRIPTION_ALREADY_USED,
            self::UPLOAD_INVALID_FILE,
            self::UPLOAD_STORAGE_FAILED => 422,

            self::AUTH_OTP_EXPIRED,
            self::AUTH_EMAIL_NOT_VERIFIED,
            self::BUSINESS_EMAIL_EXISTS,
            self::BUSINESS_LOGIN_ROLE_DENIED,
            self::BUSINESS_DUPLICATE_ENTRY => 409,

            self::AUTH_INVALID_CREDENTIALS,
            self::AUTH_TOKEN_EXPIRED,
            self::AUTH_TOKEN_INVALID,
            self::AUTH_OTP_INVALID => 401,

            self::FORBIDDEN_ROLE,
            self::FORBIDDEN_PERMISSION => 403,

            self::NOT_FOUND_RESOURCE,
            self::NOT_FOUND_RELATION => 404,

            self::EXTERNAL_MAIL_UNCONFIGURED,
            self::EXTERNAL_MAIL_FAILED,
            self::EXTERNAL_SMS_FAILED,
            self::EXTERNAL_PAYMENT_FAILED => 502,

            self::SYSTEM_TRANSACTION_FAILED,
            self::SYSTEM_INTERNAL_ERROR,
            self::SYSTEM_MAINTENANCE_MODE,
            self::BUSINESS_SALE_CANNOT_BE_DELETED,
            self::AUTH_UNAUTHORIZED => 500,
        };
    }

    public function logLevel(): string
    {
        return match($this) {
            self::SYSTEM_TRANSACTION_FAILED,
            self::SYSTEM_INTERNAL_ERROR,
            self::SYSTEM_MAINTENANCE_MODE,
            self::EXTERNAL_MAIL_FAILED,
            self::EXTERNAL_SMS_FAILED,
            self::EXTERNAL_PAYMENT_FAILED => 'critical',

            self::FORBIDDEN_ROLE,
            self::FORBIDDEN_PERMISSION,
            self::AUTH_TOKEN_EXPIRED,
            self::AUTH_INVALID_CREDENTIALS,
            self::AUTH_TOKEN_INVALID,
            self::BUSINESS_INSUFFICIENT_STOCK,
            self::EXTERNAL_MAIL_UNCONFIGURED => 'warning',

            default => 'info',
        };
    }

    public function shouldAlert(): bool
    {
        return in_array($this, [
            self::SYSTEM_TRANSACTION_FAILED,
            self::SYSTEM_INTERNAL_ERROR,
            self::EXTERNAL_MAIL_FAILED,
            self::EXTERNAL_SMS_FAILED,
            self::EXTERNAL_PAYMENT_FAILED,
            self::AUTH_TOKEN_EXPIRED, // repeated occurrences
        ]);
    }
}
```

### Base Renderable Exception

```php
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

        $message = $user ?: __('errors.' . $errorCode->value);
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

        Log::$logLevel($this->getMessage(), $logData);

        if ($this->errorCode->shouldAlert()) {
            $this->sendAlert($logData);
        }
    }

    protected function sendAlert(array $logData): void
    {
        // TODO: Integrate with Slack/Discord/Telegram for alerts
        // Notification::route('slack', config('logging.slack_webhook'))
        //     ->notify(new CriticalErrorNotification($logData));
    }
}
```

### Concrete Exception Classes

```php
<?php

namespace App\Exceptions;

use App\Exceptions\Errors\ErrorCode;

class BusinessRuleException extends RenderableException
{
    public function __construct(
        ErrorCode $errorCode = ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
        string $userMessage = '',
        array $context = [],
        array $debugData = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($errorCode, $userMessage, $context, $debugData, $previous);
    }
}

class NotFoundException extends RenderableException
{
    public function __construct(
        string $resource = 'Resource',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ErrorCode::NOT_FOUND_RESOURCE,
            __('errors.resource_not_found', ['resource' => $resource]),
            $context,
            [],
            $previous
        );
    }
}

class TransactionException extends RenderableException
{
    public function __construct(
        string $operation = '',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ErrorCode::SYSTEM_TRANSACTION_FAILED,
            __('errors.transaction_failed'),
            array_merge(['operation' => $operation], $context),
            ['previous_exception' => $previous ? $previous->getMessage() : null],
            $previous
        );
    }
}

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

class ExternalServiceException extends RenderableException
{
    public function __construct(
        string $service = '',
        string $userMessage = '',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            ErrorCode::EXTERNAL_MAIL_FAILED,
            $userMessage ?: __('errors.service_unavailable', ['service' => $service]),
            array_merge(['service' => $service], $context),
            ['previous_exception' => $previous ? $previous->getMessage() : null],
            $previous
        );
    }
}

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
```

---

## 3. Error Handling Patterns by Error Type

### Pattern A: Validation Errors - Form Request Classes

**Problem:** Current controllers call `$request->validate(...)` inline, leading to scattered validation logic.

**Solution:** Extract all validation into dedicated Form Request classes with consistent error formatting.

```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => __('errors.validation_failed'),
            'error_code' => 'VALIDATION_FAILED',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => __('errors.unauthorized_action'),
            'error_code' => 'FORBIDDEN_PERMISSION',
        ], 403));
    }
}
```

**Example: PrescriptionStoreRequest**

```php
<?php

namespace App\Http\Requests\Api;

class PrescriptionStoreRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true; // Or check permission: $this->user()->can('create prescriptions');
    }

    public function rules(): array
    {
        return [
            'party_id' => 'nullable|integer|exists:parties,id',
            'notes' => 'nullable|string|max:1000',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => __('validation.prescription_image_required'),
            'image.image' => __('validation.invalid_image_format'),
            'image.max' => __('validation.image_too_large', ['max' => '5MB']),
        ];
    }
}
```

### Pattern B: Business Rule Violations

**Problem:** Current code returns inline JSON responses for business validations.

**Solution:** Throw dedicated exceptions with proper error codes.

```php
// BEFORE (current pattern)
if ($stock->productStock < $request->products[$key]['quantities']) {
    return response()->json([
        'message' => __($stock->batch_no . ' - stock not available.')
    ], 400);
}

// AFTER (recommended)
if ($stock->productStock < $request->products[$key]['quantities']) {
    throw new BusinessRuleException(
        ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
        __('errors.insufficient_stock', [
            'product' => $request->products[$key]['product_id'],
            'batch' => $stock->batch_no,
            'available' => $stock->productStock,
            'requested' => $request->products[$key]['quantities']
        ]),
        [
            'product_id' => $request->products[$key]['product_id'],
            'batch_no' => $stock->batch_no,
            'available_qty' => $stock->productStock,
            'requested_qty' => $request->products[$key]['quantities'],
        ]
    );
}
```

### Pattern C: Database Transaction Errors

**Problem:** Generic `catch (\Exception $e)` with a vague "Something was wrong" message.

**Solution:** Structured transaction wrapper with proper error propagation.

```php
<?php

namespace App\Helpers;

use App\Exceptions\TransactionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionHelper
{
    /**
     * Execute a callback within a database transaction with proper error handling.
     *
     * @param callable $callback The business logic to execute
     * @param string $operation A descriptive name of the operation
     * @param array $context Additional context for logging
     * @return mixed
     * @throws TransactionException
     */
    public static function run(callable $callback, string $operation, array $context = []): mixed
    {
        DB::beginTransaction();

        try {
            $result = $callback();

            DB::commit();

            return $result;

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            throw new TransactionException($operation, array_merge($context, [
                'sql_error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]), $e);

        } catch (\PDOException $e) {
            DB::rollBack();

            throw new TransactionException($operation, array_merge($context, [
                'pdo_error' => $e->getMessage(),
            ]), $e);

        } catch (\Throwable $e) {
            DB::rollBack();

            // Re-throw if it's already our custom exception
            if ($e instanceof \App\Exceptions\RenderableException) {
                throw $e;
            }

            throw new TransactionException($operation, array_merge($context, [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]), $e);
        }
    }
}
```

### Pattern D: Not Found Errors

**Current:** `Model::findOrFail($id)` - This throws `ModelNotFoundException` which returns a generic 404.

**Solution:** Register a renderable callback in `Handler.php` to format ModelNotFoundException responses.

```php
// In app/Exceptions/Handler.php

public function register(): void
{
    $this->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            $model = class_basename($e->getModel());

            return response()->json([
                'success' => false,
                'message' => __('errors.resource_not_found', ['resource' => $model]),
                'error_code' => 'NOT_FOUND_RESOURCE',
                'errors' => [
                    'id' => [__('validation.exists', ['attribute' => strtolower($model)])],
                ],
            ], 404);
        }
    });
}
```

### Pattern E: Authentication Errors

**Problem:** Auth errors have mixed formats and inconsistent status codes.

**Solution:** Use Sanctum's built-in exception handling plus custom overrides in Handler.

```php
// In app/Exceptions/Handler.php

public function register(): void
{
    // ... ModelNotFoundException handler ...

    $this->renderable(function (\Laravel\Sanctum\Exceptions\MissingAbilityException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => __('errors.forbidden_permission'),
                'error_code' => 'FORBIDDEN_PERMISSION',
            ], 403);
        }
    });

    $this->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => __('errors.unauthenticated'),
                'error_code' => 'AUTH_UNAUTHORIZED',
            ], 401);
        }
    });
}
```

### Pattern F: Image Upload Errors

**Problem:** Upload failures in `HasUploader` trait are not caught.

**Solution:** Add validation and error handling to the upload helper.

```php
// In HasUploader trait - enhanced version

private function upload(Request $request, $input, $oldFile = null, $disk = null)
{
    if (!$request->hasFile($input)) {
        throw new UploadException('No file uploaded', ['input' => $input]);
    }

    $file = $request->file($input);

    if (!$file->isValid()) {
        throw new UploadException(
            $file->getErrorMessage(),
            ['input' => $input, 'original_name' => $file->getClientOriginalName()]
        );
    }

    try {
        $ext = $file->getClientOriginalExtension();
        $filename = now()->timestamp . '-' . rand(1, 1000) . '.' . $ext;
        $path = 'uploads/' . date('y') . '/' . date('m') . '/';
        $filePath = $path . $filename;

        if ($oldFile && Storage::exists($oldFile)) {
            Storage::delete($oldFile);
        }

        Storage::disk($disk ?? config('filesystems.default'))
            ->put($filePath, file_get_contents($file));

        return $filePath;

    } catch (\Throwable $e) {
        throw new UploadException(
            'Storage write failed',
            ['input' => $input, 'path' => $filePath ?? null],
            $e
        );
    }
}
```

---

## 4. User-Facing Message Strategy

### API Response Standard Format

```json
// Success Response
{
    "success": true,
    "message": "Prescription saved successfully.",
    "data": { ... }
}

// Error Response
{
    "success": false,
    "message": "Insufficient stock for batch BATCH-001. Available: 5, Requested: 10.",
    "error_code": "BUSINESS_INSUFFICIENT_STOCK",
    "errors": {
        "batch_no": ["Stock not available for batch BATCH-001"]
    }
}

// Validation Error Response
{
    "success": false,
    "message": "Validation failed for the request.",
    "error_code": "VALIDATION_FAILED",
    "errors": {
        "image": ["The image must be an image file.", "The image must not be larger than 5MB."],
        "party_id": ["The selected party id is invalid."]
    }
}

// Debug Mode (non-production)
{
    "success": false,
    "message": "...",
    "error_code": "...",
    "errors": {},
    "debug": {
        "exception": "App\\Exceptions\\TransactionException",
        "file": "/app/Http/Controllers/Api/AcnooSaleController.php",
        "line": 145,
        "trace": "#0 /vendor/laravel/..."
    }
}
```

### Translation Strings (lang/en.json, lang/ar.json)

```json
{
    "errors.validation_failed": "Validation failed for the request.",
    "errors.resource_not_found": "{resource} not found.",
    "errors.insufficient_stock": "Insufficient stock for batch {batch}. Available: {available}, Requested: {requested}.",
    "errors.due_sale_walking_customer": "You cannot make a due sale for a walking customer.",
    "errors.duplicate_entry": "{field} '{value}' already exists.",
    "errors.upload_failed": "File upload failed: {reason}",
    "errors.service_unavailable": "{service} is currently unavailable. Please try again later.",
    "errors.mail_not_configured": "Mail service is not configured. Please contact your administrator.",
    "errors.transaction_failed": "Operation failed due to a system error. Please try again.",
    "errors.unauthorized_action": "You are not authorized to perform this action.",
    "errors.unauthenticated": "Please log in to continue.",
    "errors.forbidden_role": "You do not have the required role to access this resource.",
    "errors.forbidden_permission": "You do not have the required permission.",
    "errors.token_expired": "Your session has expired. Please log in again.",
    "errors.invalid_credentials": "Invalid email or password.",
    "errors.invalid_otp": "Invalid OTP code.",
    "errors.otp_expired": "The verification OTP has expired. Please request a new one.",
    "errors.email_already_exists": "This email is already registered.",
    "errors.login_role_denied": "You cannot login as {role} from the app.",
    "errors.prescription_already_used": "This prescription has already been linked to a sale.",
    "errors.image_too_large": "Image must not exceed {max}.",
    "errors.invalid_image_format": "Invalid image format. Accepted: jpeg, png, jpg, gif, svg.",
    "errors.prescription_image_required": "A prescription image is required.",
    "errors.product_image_required": "A product image is required.",
    "errors.internal_error": "Something went wrong. Please try again later."
}
```

### Flutter/Mobile App Error Handling Map

```dart
// Flutter: error_mapper.dart
class ErrorMapper {
  static String getUserMessage(Map<String, dynamic> error) {
    final code = error['error_code'] as String?;
    
    switch (code) {
      case 'VALIDATION_FAILED':
        final errors = error['errors'] as Map<String, dynamic>?;
        if (errors != null && errors.isNotEmpty) {
          return errors.values.first.first as String;
        }
        return error['message'] as String? ?? 'Validation failed';
        
      case 'AUTH_TOKEN_EXPIRED':
        // Trigger re-login flow
        AuthService.instance.logout();
        return error['message'] as String? ?? 'Session expired';
        
      case 'BUSINESS_INSUFFICIENT_STOCK':
        return error['message'] as String? ?? 'Stock not available';
        
      case 'NOT_FOUND_RESOURCE':
        return error['message'] as String? ?? 'Resource not found';
        
      case 'EXTERNAL_MAIL_UNCONFIGURED':
        return error['message'] as String? ?? 'Email service not configured';
        
      default:
        return error['message'] as String? ?? 'Something went wrong';
    }
  }
  
  static bool isAuthError(String? code) {
    return code?.startsWith('AUTH_') ?? false;
  }
  
  static bool isRetryable(String? code) {
    return code?.startsWith('EXTERNAL_') ?? false ||
           code == 'SYSTEM_TRANSACTION_FAILED';
  }
}
```

---

## 5. Logging and Monitoring Approach

### Log Channels Configuration

```php
// config/logging.php additions

'channels' => [
    // ... existing channels ...
    
    'api' => [
        'driver' => 'daily',
        'path' => storage_path('logs/api.log'),
        'level' => 'info',
        'days' => 30,
    ],
    
    'business' => [
        'driver' => 'daily',
        'path' => storage_path('logs/business.log'),
        'level' => 'warning',
        'days' => 90,
    ],
    
    'errors' => [
        'driver' => 'daily',
        'path' => storage_path('logs/errors.log'),
        'level' => 'error',
        'days' => 90,
    ],
    
    'critical' => [
        'driver' => 'daily',
        'path' => storage_path('logs/critical.log'),
        'level' => 'critical',
        'days' => 365,
    ],
]
```

### Logging Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogApiRequests
{
    public function handle($request, Closure $next)
    {
        $requestId = (string) Str::uuid();
        $request->merge(['_request_id' => $requestId]);

        $startTime = microtime(true);

        Log::channel('api')->info('API Request', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'business_id' => auth()->user()?->business_id,
        ]);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        Log::channel('api')->info('API Response', [
            'request_id' => $requestId,
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration * 1000, 2),
        ]);

        // Alert on slow responses
        if ($duration > 2.0) {
            Log::channel('critical')->warning('Slow API Response', [
                'request_id' => $requestId,
                'url' => $request->fullUrl(),
                'duration_ms' => round($duration * 1000, 2),
            ]);
        }

        return $response;
    }
}
```

### Structured Logging Helper

```php
<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class Logger
{
    public static function business(string $operation, string $status, array $data = []): void
    {
        Log::channel('business')->log(
            $status === 'failed' ? 'warning' : 'info',
            "Business operation: {$operation}",
            array_merge([
                'operation' => $operation,
                'status' => $status,
                'user_id' => auth()->id(),
                'business_id' => auth()->user()?->business_id,
                'timestamp' => now()->toIso8601String(),
            ], $data)
        );
    }

    public static function error(\Throwable $e, array $context = []): void
    {
        $logData = array_merge([
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'message' => $e->getMessage(),
            'user_id' => auth()->id(),
            'business_id' => auth()->user()?->business_id,
            'url' => request()?->fullUrl(),
            'method' => request()?->method(),
        ], $context);

        $level = 'error';

        if ($e instanceof \App\Exceptions\RenderableException) {
            $level = $e->errorCode->logLevel();
        }

        if ($e instanceof \Illuminate\Database\QueryException) {
            $logData['sql'] = $e->getSql();
            $logData['bindings'] = $e->getBindings();
            $level = 'critical';
        }

        Log::channel('errors')->log($level, $e->getMessage(), $logData);

        if ($level === 'critical') {
            Log::channel('critical')->critical($e->getMessage(), $logData);
        }
    }
}
```

### Monitoring & Alerting Strategy

| Event | Log Channel | Log Level | Alert Method | Recipients |
|-------|-------------|-----------|--------------|------------|
| Validation failure | `api` | info | None | - |
| Business rule violation | `business` | warning | None (log only) | - |
| Not found (404) | `api` | info | None | - |
| Auth failure | `api` | warning | Count per IP, alert at threshold | Admin |
| Transaction failure | `errors` | error | Slack/Email | Dev team |
| External service failure | `errors` | critical | Slack + Email + SMS | Dev team, Ops |
| Slow API (>2s) | `critical` | warning | Slack | Dev team |
| Upload failure | `errors` | error | None (log only) | - |
| Duplicate entry | `business` | info | None | - |
| Unexcepted exception | `errors` | critical | Slack + Email | Dev team |

### Metrics to Track (for Dashboard/Monitoring)

1. **Error Rate**: Count of 5xx responses / total requests (per hour)
2. **Error Distribution**: Breakdown by `error_code`
3. **Business Rule Violations**: Count of `BUSINESS_*` errors (could indicate UX issues)
4. **Auth Failure Rate**: Ratio of successful vs failed logins
5. **API Response Times**: P50, P95, P99 latencies
6. **Transaction Success Rate**: Commit vs rollback ratio

---

## 6. Implementation Roadmap

### Phase 1: Foundation (Week 1)
1. Create `ErrorCode` enum
2. Create `RenderableException` base class
3. Create concrete exception classes
4. Update `Handler.php` with renderable callbacks
5. Add API logging middleware

### Phase 2: Controllers Refactoring (Week 2)
1. Replace inline validation with Form Request classes
2. Replace `findOrFail()` with context-aware exception handling
3. Replace generic catch blocks with specific exceptions
4. Add `TransactionHelper` usage to all transactional operations

### Phase 3: Frontend Integration (Week 3)
1. Add translation strings for all error messages
2. Create Flutter error mapping utility
3. Implement auto-logout on auth errors in mobile app
4. Add retry logic for retryable errors

### Phase 4: Monitoring (Week 4)
1. Set up log channels
2. Configure Slack/email notifications
3. Create Grafana dashboard or similar
4. Set up error alerting thresholds

---

## 7. Existing Code Migration Examples

### Example 1: Prescription Controller (Current → Target)

**Current Code** (`AcnooPrescriptionController.php`):
```php
// Current pattern - scattered, generic error handling
try {
    // business logic
} catch (\Exception $e) {
    DB::rollback();
    return response()->json([
        'message' => __('Something was wrong.'),
    ], 406);
}
```

**Target Code**:
```php
public function store(PrescriptionStoreRequest $request)
{
    $prescription = TransactionHelper::run(function () use ($request) {
        return Prescription::create([
            'business_id' => auth()->user()->business_id,
            'party_id' => $request->party_id,
            'notes' => $request->notes,
            'image' => $this->upload($request, 'image'),
            'status' => 'pending',
            'meta' => [
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now()->toDateTimeString(),
            ],
        ]);
    }, 'prescription:store', ['party_id' => $request->party_id]);

    return response()->json([
        'message' => __('Prescription saved successfully.'),
        'data' => $prescription->load(['party:id,name,phone']),
    ]);
}
```

### Example 2: Sale Controller Stock Check

**Current**:
```php
if ($stock->productStock < $request->products[$key]['quantities']) {
    return response()->json([
        'message' => __($stock->batch_no . ' - stock not available.')
    ], 400);
}
```

**Target**:
```php
throw new BusinessRuleException(
    ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
    __('errors.insufficient_stock', [
        'product' => $product->productName ?? $productData['product_id'],
        'batch' => $productData['batch_no'],
        'available' => $stock->productStock,
        'requested' => $productData['quantities'],
    ]),
    [
        'batch_no' => $productData['batch_no'],
        'product_id' => $productData['product_id'],
        'available_qty' => $stock->productStock,
        'requested_qty' => $productData['quantities'],
    ]
);
```

---

## 8. Handler.php Final Structure

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // Custom renderable exceptions (already handled by their render() method)
        
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
                    'error_code' => 'NOT_FOUND_RESOURCE',
                ], 404);
            }
        });

        // Method not allowed
        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => __('errors.method_not_allowed'),
                    'error_code' => 'VALIDATION_INVALID_INPUT',
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
    }

    public function report(Throwable $e): void
    {
        if ($e instanceof RenderableException) {
            $e->report();
            return;
        }

        // Log all unhandled exceptions to errors channel
        \App\Helpers\Logger::error($e);

        parent::report($e);
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            // If it's our custom exception, use its render method
            if ($e instanceof RenderableException) {
                return $e->render($request);
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

            return response()->json($response, $status);
        }

        return parent::render($request, $e);
    }
}
```

---

## Summary

| Aspect | Current State | Target State |
|--------|---------------|--------------|
| Error classification | None (all generic) | 9 categories with `ErrorCode` enum |
| Exception classes | None | Base + 7 concrete exception classes |
| Validation | Inline `$request->validate()` | Dedicated Form Request classes |
| Business rules | Inline JSON returns | Thrown exceptions with codes |
| Transactions | Manual try-catch | `TransactionHelper` wrapper |
| API response format | Inconsistent | Standardized `{success, message, error_code, errors}` |
| HTTP status codes | Arbitrary (400, 404, 406) | Semantic (422, 409, 404, 401, etc.) |
| User messages | Hardcoded English | Translatable via `__('errors.*')` |
| Logging | Default Laravel only | Structured channels (api, business, errors, critical) |
| Monitoring | None | Log levels + alerts + metrics |
| Flutter handling | None | `ErrorMapper` utility class |

