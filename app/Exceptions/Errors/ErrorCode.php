<?php

namespace App\Exceptions\Errors;

/**
 * Error codes for the application's exception system.
 *
 * Each code carries metadata: HTTP status, log level, and alert flag.
 */
enum ErrorCode: string
{
    // Business Rule Errors
    case BUSINESS_INSUFFICIENT_STOCK = 'BUSINESS_INSUFFICIENT_STOCK';
    case BUSINESS_PRODUCT_NOT_FOUND = 'BUSINESS_PRODUCT_NOT_FOUND';
    case BUSINESS_DUPLICATE_INVOICE = 'BUSINESS_DUPLICATE_INVOICE';
    case BUSINESS_INVALID_DATE_RANGE = 'BUSINESS_INVALID_DATE_RANGE';
    case BUSINESS_INSUFFICIENT_PERMISSION = 'BUSINESS_INSUFFICIENT_PERMISSION';
    case BUSINESS_DUE_SALE_WALKING_CUSTOMER = 'BUSINESS_DUE_SALE_WALKING_CUSTOMER';
    case BUSINESS_BATCH_QUANTITY_MISMATCH = 'BUSINESS_BATCH_QUANTITY_MISMATCH';
    case BUSINESS_SUBSCRIPTION_EXPIRED = 'BUSINESS_SUBSCRIPTION_EXPIRED';
    case BUSINESS_SUBSCRIPTION_LIMIT_EXCEEDED = 'BUSINESS_SUBSCRIPTION_LIMIT_EXCEEDED';

    // Validation Errors
    case VALIDATION_FAILED = 'VALIDATION_FAILED';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case NOT_FOUND_BATCH = 'NOT_FOUND_BATCH';
    case VALIDATION_ROUTE_NOT_FOUND = 'VALIDATION_ROUTE_NOT_FOUND';
    case VALIDATION_METHOD_NOT_ALLOWED = 'VALIDATION_METHOD_NOT_ALLOWED';

    // Authentication Errors
    case AUTH_UNAUTHORIZED = 'AUTH_UNAUTHORIZED';
    case AUTH_FORBIDDEN = 'AUTH_FORBIDDEN';
    case AUTH_TOKEN_EXPIRED = 'AUTH_TOKEN_EXPIRED';
    case AUTH_INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS';

    // System Errors
    case SYSTEM_INTERNAL_ERROR = 'SYSTEM_INTERNAL_ERROR';
    case SYSTEM_DATABASE_ERROR = 'SYSTEM_DATABASE_ERROR';
    case SYSTEM_EXTERNAL_SERVICE_ERROR = 'SYSTEM_EXTERNAL_SERVICE_ERROR';
    case SYSTEM_RATE_LIMIT_EXCEEDED = 'SYSTEM_RATE_LIMIT_EXCEEDED';

    // Resource Errors
    case RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    case RESOURCE_ALREADY_EXISTS = 'RESOURCE_ALREADY_EXISTS';
    case RESOURCE_CONFLICT = 'RESOURCE_CONFLICT';

    // Invoice/Due Errors
    case BUSINESS_INVOICE_NOT_FOUND = 'BUSINESS_INVOICE_NOT_FOUND';
    case BUSINESS_INVOICE_DUE_EXCEEDED = 'BUSINESS_INVOICE_DUE_EXCEEDED';
    case BUSINESS_OPENING_BALANCE_EXCEEDED = 'BUSINESS_OPENING_BALANCE_EXCEEDED';
    case BUSINESS_DUPLICATE_ENTRY = 'BUSINESS_DUPLICATE_ENTRY';

    // Validation/Upload Errors
    case VALIDATION_MISSING_FIELD = 'VALIDATION_MISSING_FIELD';
    case UPLOAD_STORAGE_FAILED = 'UPLOAD_STORAGE_FAILED';
    case SYSTEM_TRANSACTION_FAILED = 'SYSTEM_TRANSACTION_FAILED';
    case RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';
    case EXTERNAL_MAIL_FAILED = 'EXTERNAL_MAIL_FAILED';

    /**
     * Get the appropriate HTTP status code for this error.
     */
    public function httpStatus(): int
    {
        return match ($this) {
            self::AUTH_UNAUTHORIZED => 401,
            self::AUTH_FORBIDDEN,
            self::AUTH_TOKEN_EXPIRED,
            self::AUTH_INVALID_CREDENTIALS => 403,
            self::VALIDATION_FAILED,
            self::VALIDATION_ERROR,
            self::VALIDATION_ROUTE_NOT_FOUND,
            self::VALIDATION_METHOD_NOT_ALLOWED,
            self::BUSINESS_INSUFFICIENT_STOCK,
            self::BUSINESS_PRODUCT_NOT_FOUND,
            self::BUSINESS_DUPLICATE_INVOICE,
            self::BUSINESS_INVALID_DATE_RANGE,
            self::BUSINESS_INSUFFICIENT_PERMISSION,
            self::BUSINESS_DUE_SALE_WALKING_CUSTOMER,
            self::BUSINESS_BATCH_QUANTITY_MISMATCH,
            self::BUSINESS_SUBSCRIPTION_EXPIRED,
            self::BUSINESS_SUBSCRIPTION_LIMIT_EXCEEDED,
            self::NOT_FOUND_BATCH => 422,
            self::RESOURCE_NOT_FOUND => 404,
            self::RESOURCE_ALREADY_EXISTS,
            self::RESOURCE_CONFLICT => 409,
            default => 500,
        };
    }

    /**
     * Get the log level for this error.
     */
    public function logLevel(): string
    {
        return match ($this) {
            self::SYSTEM_INTERNAL_ERROR,
            self::SYSTEM_DATABASE_ERROR,
            self::SYSTEM_EXTERNAL_SERVICE_ERROR => 'critical',
            self::AUTH_UNAUTHORIZED,
            self::AUTH_FORBIDDEN,
            self::AUTH_TOKEN_EXPIRED,
            self::AUTH_INVALID_CREDENTIALS => 'warning',
            default => 'error',
        };
    }

    /**
     * Whether this error should trigger an alert.
     */
    public function shouldAlert(): bool
    {
        return match ($this) {
            self::SYSTEM_INTERNAL_ERROR,
            self::SYSTEM_DATABASE_ERROR,
            self::SYSTEM_EXTERNAL_SERVICE_ERROR => true,
            default => false,
        };
    }

    /**
     * Get human-readable message for error code.
     */
    public static function getMessage(ErrorCode $code): string
    {
        return match ($code) {
            self::BUSINESS_INSUFFICIENT_STOCK => __('Insufficient stock available'),
            self::BUSINESS_PRODUCT_NOT_FOUND => __('Product not found'),
            self::BUSINESS_DUPLICATE_INVOICE => __('Duplicate invoice number'),
            self::BUSINESS_INVALID_DATE_RANGE => __('Invalid date range'),
            self::BUSINESS_INSUFFICIENT_PERMISSION => __('Insufficient permissions'),
            self::BUSINESS_DUE_SALE_WALKING_CUSTOMER => __('Due sale not allowed for walking customers'),
            self::BUSINESS_BATCH_QUANTITY_MISMATCH => __('Batch quantity mismatch'),
            self::BUSINESS_SUBSCRIPTION_EXPIRED => __('Subscription has expired'),
            self::BUSINESS_SUBSCRIPTION_LIMIT_EXCEEDED => __('Subscription limit exceeded'),
            self::VALIDATION_FAILED => __('Validation failed'),
            self::VALIDATION_ERROR => __('Validation error'),
            self::NOT_FOUND_BATCH => __('Batch not found'),
            self::AUTH_UNAUTHORIZED => __('Unauthorized'),
            self::AUTH_FORBIDDEN => __('Forbidden'),
            self::SYSTEM_INTERNAL_ERROR => __('Internal server error'),
            self::SYSTEM_DATABASE_ERROR => __('Database error'),
            self::RESOURCE_NOT_FOUND => __('Resource not found'),
            self::RESOURCE_ALREADY_EXISTS => __('Resource already exists'),
            default => __('An error occurred'),
        };
    }
}
