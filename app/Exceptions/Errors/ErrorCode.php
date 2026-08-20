<?php

namespace App\Exceptions\Errors;

class ErrorCode
{
    // Business Rule Errors
    public const BUSINESS_INSUFFICIENT_STOCK = 'BUSINESS_INSUFFICIENT_STOCK';
    public const BUSINESS_PRODUCT_NOT_FOUND = 'BUSINESS_PRODUCT_NOT_FOUND';
    public const BUSINESS_DUPLICATE_INVOICE = 'BUSINESS_DUPLICATE_INVOICE';
    public const BUSINESS_INVALID_DATE_RANGE = 'BUSINESS_INVALID_DATE_RANGE';
    public const BUSINESS_INSUFFICIENT_PERMISSION = 'BUSINESS_INSUFFICIENT_PERMISSION';
    public const BUSINESS_DUE_SALE_WALKING_CUSTOMER = 'BUSINESS_DUE_SALE_WALKING_CUSTOMER';
    public const BUSINESS_SUBSCRIPTION_EXPIRED = 'BUSINESS_SUBSCRIPTION_EXPIRED';
    public const BUSINESS_SUBSCRIPTION_LIMIT_EXCEEDED = 'BUSINESS_SUBSCRIPTION_LIMIT_EXCEEDED';

    // Validation Errors
    public const VALIDATION_FAILED = 'VALIDATION_FAILED';
    public const VALIDATION_ROUTE_NOT_FOUND = 'VALIDATION_ROUTE_NOT_FOUND';
    public const VALIDATION_METHOD_NOT_ALLOWED = 'VALIDATION_METHOD_NOT_ALLOWED';

    // Authentication Errors
    public const AUTH_UNAUTHORIZED = 'AUTH_UNAUTHORIZED';
    public const AUTH_FORBIDDEN = 'AUTH_FORBIDDEN';
    public const AUTH_TOKEN_EXPIRED = 'AUTH_TOKEN_EXPIRED';
    public const AUTH_INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS';

    // System Errors
    public const SYSTEM_INTERNAL_ERROR = 'SYSTEM_INTERNAL_ERROR';
    public const SYSTEM_DATABASE_ERROR = 'SYSTEM_DATABASE_ERROR';
    public const SYSTEM_EXTERNAL_SERVICE_ERROR = 'SYSTEM_EXTERNAL_SERVICE_ERROR';
    public const SYSTEM_RATE_LIMIT_EXCEEDED = 'SYSTEM_RATE_LIMIT_EXCEEDED';

    // Resource Errors
    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const RESOURCE_ALREADY_EXISTS = 'RESOURCE_ALREADY_EXISTS';
    public const RESOURCE_CONFLICT = 'RESOURCE_CONFLICT';

    /**
     * Get human-readable message for error code
     */
    public static function getMessage(string $code): string
    {
        return match ($code) {
            self::BUSINESS_INSUFFICIENT_STOCK => __('Insufficient stock available'),
            self::BUSINESS_PRODUCT_NOT_FOUND => __('Product not found'),
            self::BUSINESS_DUPLICATE_INVOICE => __('Duplicate invoice number'),
            self::BUSINESS_INVALID_DATE_RANGE => __('Invalid date range'),
            self::BUSINESS_INSUFFICIENT_PERMISSION => __('Insufficient permissions'),
            self::BUSINESS_DUE_SALE_WALKING_CUSTOMER => __('Due sale not allowed for walking customers'),
            self::BUSINESS_SUBSCRIPTION_EXPIRED => __('Subscription has expired'),
            self::BUSINESS_SUBSCRIPTION_LIMIT_EXCEEDED => __('Subscription limit exceeded'),
            self::VALIDATION_FAILED => __('Validation failed'),
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
