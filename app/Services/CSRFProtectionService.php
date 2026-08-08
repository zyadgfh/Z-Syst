<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CSRFProtectionService
{
    /**
     * Generate secure CSRF token
     */
    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validate CSRF token with timing attack protection
     */
    public function validateToken(string $token, string $sessionToken): bool
    {
        return hash_equals($sessionToken, $token);
    }

    /**
     * Get current CSRF token from session
     */
    public function getToken(): string
    {
        return Session::token();
    }

    /**
     * Regenerate CSRF token
     */
    public function regenerateToken(): string
    {
        Session::regenerateToken();
        return $this->getToken();
    }

    /**
     * Verify CSRF token from request
     */
    public function verifyToken(string $token): bool
    {
        return $this->validateToken($token, $this->getToken());
    }

    /**
     * Get CSRF token for AJAX requests
     */
    public function getAjaxToken(): string
    {
        return $this->getToken();
    }

    /**
     * Check if token is expired (optional implementation)
     */
    public function isTokenExpired(string $token, int $maxAge = 3600): bool
    {
        // This would require storing token creation time
        // For now, Laravel handles token rotation automatically
        return false;
    }

    /**
     * Generate CSRF token for form
     */
    public function generateFormField(): string
    {
        $token = $this->getToken();
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }

    /**
     * Generate CSRF meta tag for AJAX
     */
    public function generateMetaTag(): string
    {
        $token = $this->getToken();
        return '<meta name="csrf-token" content="' . $token . '">';
    }

    /**
     * Validate CSRF token from header
     */
    public function validateHeaderToken(string $headerToken): bool
    {
        return $this->validateToken($headerToken, $this->getToken());
    }

    /**
     * Get CSRF token as JavaScript variable
     */
    public function getJavaScriptToken(): string
    {
        $token = $this->getToken();
        return "window.csrfToken = '{$token}';";
    }
}
