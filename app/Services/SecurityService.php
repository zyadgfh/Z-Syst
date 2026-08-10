<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SecurityService
{
    /**
     * Sanitize user input to prevent XSS attacks
     */
    public function sanitizeInput(array $data): array
    {
        return collect($data)->map(function ($value) {
            if (is_string($value)) {
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            if (is_array($value)) {
                return $this->sanitizeInput($value);
            }
            return $value;
        })->toArray();
    }

    /**
     * Validate SQL injection attempts
     */
    public function detectSqlInjection(string $input): bool
    {
        $sqlPatterns = [
            '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|EXEC|ALTER|CREATE|TRUNCATE)\b/i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+/i',
            '/\b(OR|AND)\s+\w+\s*=\s*\w+/i',
            '/[\'"]\s*(OR|AND)\s*[\'"]/i',
            '/--/',
            '/\/\*/',
            '/\*.*\*\//',
            '/;\s*DROP/',
            '/;\s*DELETE/',
            '/;\s*UPDATE/',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate XSS attempts
     */
    public function detectXss(string $input): bool
    {
        $xssPatterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
            '/<object\b[^>]*>(.*?)<\/object>/is',
            '/<embed\b[^>]*>(.*?)<\/embed>/is',
            '/on\w+\s*=/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/data:text\/html/i',
            '/<\?php/i',
            '/<\%/i',
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate and sanitize file uploads
     */
    public function validateFileUpload($file, array $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']): bool
    {
        if (!$file) {
            return false;
        }

        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return false;
        }

        // Check MIME type
        $mime = $file->getMimeType();
        if (!in_array($mime, $allowedMimes)) {
            return false;
        }

        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        return true;
    }

    /**
     * Generate secure random token
     */
    public function generateSecureToken(int $length = 32): string
    {
        return Str::random($length);
    }

    /**
     * Validate email format strictly
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL format
     */
    public function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check for command injection
     */
    public function detectCommandInjection(string $input): bool
    {
        $commandPatterns = [
            '/[;&|`$()]/',
            '/>/',
            '/</',
            '/\|\|/',
            '/&&/',
        ];

        foreach ($commandPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate phone number format
     */
    public function validatePhone(string $phone): bool
    {
        return preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $phone) === 1;
    }

    /**
     * Sanitize filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Remove leading/trailing dots
        $filename = trim($filename, '.');
        
        // Limit length
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }
        
        return $filename;
    }

    /**
     * Validate IP address
     */
    public function validateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Rate limiting check
     */
    public function checkRateLimit(string $identifier, int $maxAttempts = 5, int $decayMinutes = 1): bool
    {
        $key = "rate_limit:{$identifier}";
        $attempts = cache()->get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        cache()->put($key, $attempts + 1, now()->addMinutes($decayMinutes));
        return true;
    }

    /**
     * Comprehensive security validation
     */
    public function validateRequest(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors(),
            ];
        }

        // Additional security checks
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                if ($this->detectSqlInjection($value)) {
                    return [
                        'valid' => false,
                        'errors' => ["{$key} contains potentially malicious SQL pattern"],
                    ];
                }
                
                if ($this->detectXss($value)) {
                    return [
                        'valid' => false,
                        'errors' => ["{$key} contains potentially malicious XSS pattern"],
                    ];
                }
                
                if ($this->detectCommandInjection($value)) {
                    return [
                        'valid' => false,
                        'errors' => ["{$key} contains potentially malicious command pattern"],
                    ];
                }
            }
        }

        return [
            'valid' => true,
            'errors' => [],
        ];
    }
}