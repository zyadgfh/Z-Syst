<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pattern detection and security event logging — NOT a defence.
 *
 * Everything here is a regex blacklist. Blacklists are for noticing traffic,
 * never for making data safe: they miss encodings they were not written for,
 * and when they do match they destroy legitimate input (a product description
 * containing the word "select", a note containing "<3"). That is why this
 * class has no production caller any more — SecurityCheck, the middleware that
 * used it to rewrite request input, was removed on 2026-11-13.
 *
 * What actually protects this application:
 *   SQL     — Eloquent's query builder and bound parameters (no string
 *             concatenation anywhere; every whereRaw/selectRaw passes ?).
 *   XSS     — Blade escapes on output by default, and the five raw {!! !!}
 *             sites either emit server-generated barcode SVG or go through
 *             App\Support\HtmlSanitizer::clean().
 *   Uploads — config('integrations.uploads') allowlist, checked against the
 *             sniffed MIME as well as the extension, in HasUploader.
 *
 * escapeString() was removed with that middleware: it was addslashes(), which
 * is not an SQL escaping function (it does not know the connection's charset
 * and it does not escape identifiers). Leaving a method with that name in a
 * class called SecurityService is an invitation to reintroduce injection.
 */
class SecurityService
{
    /**
     * Sanitize input to prevent SQL injection
     */
    public function sanitizeInput(string $input): string
    {
        // Strip HTML tags (XSS payloads like <script>) first...
        $input = strip_tags($input);

        // ...then remove potential SQL injection patterns
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
