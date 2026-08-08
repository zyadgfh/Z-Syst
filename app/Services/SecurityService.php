<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SecurityService
{
    /**
     * Sanitize input to prevent SQL injection
     */
    public function sanitizeInput(string $input): string
    {
        // Remove potential SQL injection patterns
        $patterns = [
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(;|--|\/\*|\*\/)/',
        ];

        return preg_replace($patterns, '', $input);
    }

    /**
     * Validate SQL query to prevent injection
     */
    public function validateQuery(string $query): bool
    {
        // Check for dangerous patterns
        $dangerousPatterns = [
            '/\bDROP\b.*\bTABLE\b/i',
            '/\bTRUNCATE\b.*\bTABLE\b/i',
            '/\bDELETE\b.*\bFROM\b.*\bWHERE\b.*1\s*=\s*1/i',
            '/\bUNION\b.*\bSELECT\b/i',
            '/;.*\bDROP\b/i',
            '/;.*\bDELETE\b/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Escape string for safe use in raw queries
     */
    public function escapeString(string $value): string
    {
        return addslashes($value);
    }

    /**
     * Validate table name to prevent injection
     */
    public function validateTableName(string $tableName): bool
    {
        // Only allow alphanumeric and underscores
        return preg_match('/^[a-zA-Z0-9_]+$/', $tableName) === 1;
    }

    /**
     * Validate column name to prevent injection
     */
    public function validateColumnName(string $columnName): bool
    {
        // Only allow alphanumeric, underscores, and dots
        return preg_match('/^[a-zA-Z0-9_.]+$/', $columnName) === 1;
    }

    /**
     * Sanitize array of inputs
     */
    public function sanitizeArray(array $inputs): array
    {
        return array_map(function ($input) {
            if (is_string($input)) {
                return $this->sanitizeInput($input);
            }
            if (is_array($input)) {
                return $this->sanitizeArray($input);
            }
            return $input;
        }, $inputs);
    }

    /**
     * Check for XSS in input
     */
    public function checkXSS(string $input): bool
    {
        $xssPatterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
            '/javascript:/i',
            '/on\w+\s*=/i', // onclick=, onerror=, etc.
            '/<\?php/i',
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize HTML to prevent XSS
     */
    public function sanitizeHTML(string $html): string
    {
        // Remove dangerous tags and attributes
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);
        $html = preg_replace('/javascript:/i', '', $html);
        $html = preg_replace('/on\w+\s*=/i', '', $html);
        
        return $html;
    }

    /**
     * Generate CSRF token for forms
     */
    public function generateCSRFToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validate CSRF token
     */
    public function validateCSRFToken(string $token, string $sessionToken): bool
    {
        return hash_equals($sessionToken, $token);
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        \Log::warning('Security Event: ' . $event, $context);
    }
}
