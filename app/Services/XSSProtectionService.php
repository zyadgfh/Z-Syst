<?php

namespace App\Services;

use Illuminate\Support\Str;

class XSSProtectionService
{
    /**
     * Sanitize HTML input to prevent XSS attacks
     */
    public function sanitizeHTML(string $html): string
    {
        // Remove script tags
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        
        // Remove iframe tags
        $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);
        
        // Remove object tags
        $html = preg_replace('/<object\b[^>]*>(.*?)<\/object>/is', '', $html);
        
        // Remove embed tags
        $html = preg_replace('/<embed\b[^>]*>/is', '', $html);
        
        // Remove javascript: protocol
        $html = preg_replace('/javascript:/i', '', $html);
        
        // Remove on* event handlers
        $html = preg_replace('/on\w+\s*=/i', '', $html);
        
        // Remove data: protocol with base64
        $html = preg_replace('/data:[^;]*;base64,[a-z0-9+/=]+/i', '', $html);
        
        // Remove vbscript: protocol
        $html = preg_replace('/vbscript:/i', '', $html);
        
        return $html;
    }

    /**
     * Escape HTML entities for safe output
     */
    public function escapeHTML(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize URL to prevent XSS
     */
    public function sanitizeURL(string $url): string
    {
        // Remove javascript: protocol
        $url = preg_replace('/^javascript:/i', '', $url);
        
        // Remove data: protocol
        $url = preg_replace('/^data:/i', '', $url);
        
        // Remove vbscript: protocol
        $url = preg_replace('/^vbscript:/i', '', $url);
        
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * Validate HTML content
     */
    public function validateHTML(string $html): bool
    {
        $dangerousPatterns = [
            '/<script\b/i',
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/data:[^;]*;base64/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $html)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clean user input for safe display
     */
    public function cleanUserInput(string $input): string
    {
        // First, escape HTML entities
        $cleaned = $this->escapeHTML($input);
        
        // Then, remove any remaining dangerous patterns
        $cleaned = $this->sanitizeHTML($cleaned);
        
        return $cleaned;
    }

    /**
     * Allow only specific HTML tags (whitelist approach)
     */
    public function stripTagsExcept(string $html, array $allowedTags = ['<p>', '<br>', '<strong>', '<em>']): string
    {
        $allowedString = implode('', $allowedTags);
        return strip_tags($html, $allowedString);
    }

    /**
     * Check for XSS in JSON data
     */
    public function sanitizeJSON(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->cleanUserInput($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeJSON($value);
            }
        }

        return $data;
    }

    /**
     * Generate safe attribute value
     */
    public function safeAttribute(string $value): string
    {
        return $this->escapeHTML(trim($value));
    }

    /**
     * Sanitize filename to prevent path traversal
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = str_replace(['../', '..\\', './', '.\\'], '', $filename);
        
        // Remove null bytes
        $filename = str_replace("\0", '', $filename);
        
        // Allow only safe characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        return $filename;
    }

    /**
     * Validate and sanitize CSS
     */
    public function sanitizeCSS(string $css): string
    {
        // Remove javascript: in CSS
        $css = preg_replace('/javascript:/i', '', $css);
        
        // Remove expression() in CSS (IE only)
        $css = preg_replace('/expression\s*\(/i', '', $css);
        
        // Remove @import with javascript:
        $css = preg_replace('/@import\s+url\s*\(\s*["\']?javascript:/i', '', $css);
        
        return $css;
    }
}
