<?php

namespace Tests\Feature;

use App\Services\SecurityService;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    protected SecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new SecurityService;
    }

    public function test_sql_injection_detection()
    {
        $this->assertTrue($this->securityService->detectSqlInjection("'; DROP TABLE products; --"));
        $this->assertTrue($this->securityService->detectSqlInjection("1 UNION SELECT * FROM users"));
        $this->assertTrue($this->securityService->detectSqlInjection("1 OR 1=1"));
        $this->assertTrue($this->securityService->detectSqlInjection("admin'--"));
    }

    public function test_sql_injection_allows_safe_input()
    {
        $this->assertFalse($this->securityService->detectSqlInjection('Hello World'));
        $this->assertFalse($this->securityService->detectSqlInjection('product-123'));
        $this->assertFalse($this->securityService->detectSqlInjection('normal search query'));
    }

    public function test_xss_detection()
    {
        $this->assertTrue($this->securityService->detectXss('<script>alert("XSS")</script>'));
        $this->assertTrue($this->securityService->detectXss('<iframe src="evil.com"></iframe>'));
        $this->assertTrue($this->securityService->detectXss('<object data="evil.swf"></object>'));
        $this->assertTrue($this->securityService->detectXss('javascript:alert(1)'));
        $this->assertTrue($this->securityService->detectXss('onclick=alert(1)'));
    }

    public function test_xss_allows_safe_input()
    {
        $this->assertFalse($this->securityService->detectXss('Hello World'));
        $this->assertFalse($this->securityService->detectXss('John Doe'));
        $this->assertFalse($this->securityService->detectXss('product name <b>bold</b>'));
    }

    public function test_command_injection_detection()
    {
        $this->assertTrue($this->securityService->detectCommandInjection('ls; rm -rf /'));
        $this->assertTrue($this->securityService->detectCommandInjection('test && cat /etc/passwd'));
        $this->assertTrue($this->securityService->detectCommandInjection('test | grep root'));
        $this->assertTrue($this->securityService->detectCommandInjection('$(whoami)'));
    }

    public function test_command_injection_allows_safe_input()
    {
        $this->assertFalse($this->securityService->detectCommandInjection('Hello World'));
        $this->assertFalse($this->securityService->detectCommandInjection('normal text'));
        $this->assertFalse($this->securityService->detectCommandInjection('12345'));
    }

    public function test_input_sanitization()
    {
        $maliciousArray = [
            'name' => "'; DROP TABLE products; --",
            'description' => '<script>alert("XSS")</script>',
            'safe_field' => 'normal text',
        ];

        $sanitized = $this->securityService->sanitizeInput($maliciousArray);

        // htmlspecialchars encodes < > ' etc. but keeps the text content
        $this->assertStringNotContainsString('<script>', $sanitized['description']);
        $this->assertStringContainsString('alert', $sanitized['description']); // HTML-encoded
        $this->assertEquals('normal text', $sanitized['safe_field']);
    }

    public function test_single_value_sanitization()
    {
        $malicious = ['input' => '<script>alert("XSS")</script>Hello'];
        $sanitized = $this->securityService->sanitizeInput($malicious);

        $this->assertStringNotContainsString('<script>', $sanitized['input']);
        $this->assertStringContainsString('Hello', $sanitized['input']);
    }

    public function test_secure_token_generation()
    {
        $token = $this->securityService->generateSecureToken();

        $this->assertIsString($token);
        $this->assertEquals(32, strlen($token));
    }

    public function test_secure_token_custom_length()
    {
        $token = $this->securityService->generateSecureToken(64);

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
    }

    public function test_email_validation()
    {
        $this->assertTrue($this->securityService->validateEmail('user@example.com'));
        $this->assertTrue($this->securityService->validateEmail('admin@company.org'));
        $this->assertFalse($this->securityService->validateEmail('not-an-email'));
        $this->assertFalse($this->securityService->validateEmail('missing@.com'));
    }

    public function test_url_validation()
    {
        $this->assertTrue($this->securityService->validateUrl('https://example.com'));
        $this->assertTrue($this->securityService->validateUrl('http://localhost:8000'));
        $this->assertFalse($this->securityService->validateUrl('not-a-url'));
    }

    public function test_phone_validation()
    {
        $this->assertTrue($this->securityService->validatePhone('+1234567890'));
        $this->assertTrue($this->securityService->validatePhone('01234567890'));
        $this->assertFalse($this->securityService->validatePhone('123'));
    }

    public function test_ip_validation()
    {
        $this->assertTrue($this->securityService->validateIp('192.168.1.1'));
        $this->assertTrue($this->securityService->validateIp('127.0.0.1'));
        $this->assertFalse($this->securityService->validateIp('not-an-ip'));
        $this->assertFalse($this->securityService->validateIp('999.999.999.999'));
    }

    public function test_filename_sanitization()
    {
        $this->assertEquals('test_file_.jpg', $this->securityService->sanitizeFilename('test file!.jpg'));
        $this->assertEquals('normal_file.pdf', $this->securityService->sanitizeFilename('normal_file.pdf'));
        $this->assertEquals('a', $this->securityService->sanitizeFilename('...a...'));
    }

    public function test_comprehensive_request_validation_rejects_malicious()
    {
        $data = ['name' => "'; DROP TABLE users; --", 'email' => 'test@example.com'];
        $rules = ['name' => 'required|string', 'email' => 'required|email'];

        $result = $this->securityService->validateRequest($data, $rules);

        $this->assertFalse($result['valid']);
    }

    public function test_comprehensive_request_validation_accepts_safe()
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $rules = ['name' => 'required|string', 'email' => 'required|email'];

        $result = $this->securityService->validateRequest($data, $rules);

        $this->assertTrue($result['valid']);
    }

    public function test_comprehensive_request_validation_rejects_xss()
    {
        $data = ['comment' => '<script>alert("XSS")</script>'];
        $rules = ['comment' => 'required|string'];

        $result = $this->securityService->validateRequest($data, $rules);

        $this->assertFalse($result['valid']);
    }

    public function test_file_upload_validation_rejects_oversized_file()
    {
        // Test with null file
        $this->assertFalse($this->securityService->validateFileUpload(null));
    }

    public function test_rate_limiting_works()
    {
        // First attempt should succeed
        $this->assertTrue($this->securityService->checkRateLimit('test-user', 3, 60));

        // Second and third should succeed
        $this->assertTrue($this->securityService->checkRateLimit('test-user', 3, 60));
        $this->assertTrue($this->securityService->checkRateLimit('test-user', 3, 60));

        // Fourth should fail (exceeded max attempts)
        $this->assertFalse($this->securityService->checkRateLimit('test-user', 3, 60));
    }
}
