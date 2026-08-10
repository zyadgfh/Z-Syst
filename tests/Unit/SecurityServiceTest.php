<?php

namespace Tests\Unit;

use App\Services\SecurityService;
use Tests\TestCase;

class SecurityServiceTest extends TestCase
{
    protected SecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new SecurityService();
    }

    public function test_detects_sql_injection()
    {
        $maliciousInput = "1' OR '1'='1";
        $this->assertTrue($this->securityService->detectSqlInjection($maliciousInput));
    }

    public function test_does_not_detect_safe_input_as_sql_injection()
    {
        $safeInput = "Product Name 123";
        $this->assertFalse($this->securityService->detectSqlInjection($safeInput));
    }

    public function test_detects_xss()
    {
        $maliciousInput = "<script>alert('xss')</script>";
        $this->assertTrue($this->securityService->detectXss($maliciousInput));
    }

    public function test_does_not_detect_safe_input_as_xss()
    {
        $safeInput = "Regular text with < and > symbols";
        $this->assertFalse($this->securityService->detectXss($safeInput));
    }

    public function test_detects_command_injection()
    {
        $maliciousInput = "file.txt; rm -rf /";
        $this->assertTrue($this->securityService->detectCommandInjection($maliciousInput));
    }

    public function test_sanitize_input_removes_xss()
    {
        $input = "<script>alert('test')</script>Hello";
        $sanitized = $this->securityService->sanitizeInput(['content' => $input]);
        
        $this->assertNotStringContainsString('<script>', $sanitized['content']);
        $this->assertStringContainsString('Hello', $sanitized['content']);
    }

    public function test_validate_email()
    {
        $this->assertTrue($this->securityService->validateEmail('test@example.com'));
        $this->assertFalse($this->securityService->validateEmail('invalid-email'));
    }

    public function test_validate_phone()
    {
        $this->assertTrue($this->securityService->validatePhone('+201234567890'));
        $this->assertFalse($this->securityService->validatePhone('invalid'));
    }

    public function test_generates_secure_token()
    {
        $token = $this->securityService->generateSecureToken(32);
        $this->assertStringLength($token, 32);
    }
}