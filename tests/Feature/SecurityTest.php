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

    public function test_sql_injection_prevention()
    {
        $maliciousInput = "'; DROP TABLE products; --";
        $sanitized = $this->securityService->sanitizeInput($maliciousInput);

        $this->assertNotEquals($maliciousInput, $sanitized);
        $this->assertStringNotContainsString('DROP TABLE', $sanitized);
    }

    public function test_xss_detection()
    {
        $xssInput = '<script>alert("XSS")</script>';
        $isXSS = $this->securityService->checkXSS($xssInput);

        $this->assertTrue($isXSS);
    }

    public function test_xss_sanitization()
    {
        $xssInput = '<script>alert("XSS")</script>Hello';
        $sanitized = $this->securityService->sanitizeHTML($xssInput);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('Hello', $sanitized);
    }

    public function test_csrf_token_generation()
    {
        $token = $this->securityService->generateCSRFToken();

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes * 2 (hex)
    }

    public function test_csrf_token_validation()
    {
        $token = $this->securityService->generateCSRFToken();
        $isValid = $this->securityService->validateCSRFToken($token, $token);

        $this->assertTrue($isValid);
    }

    public function test_csrf_token_validation_fails_with_wrong_token()
    {
        $token1 = $this->securityService->generateCSRFToken();
        $token2 = $this->securityService->generateCSRFToken();
        $isValid = $this->securityService->validateCSRFToken($token1, $token2);

        $this->assertFalse($isValid);
    }

    public function test_table_name_validation()
    {
        $this->assertTrue($this->securityService->validateTableName('products'));
        $this->assertTrue($this->securityService->validateTableName('user_roles'));
        $this->assertFalse($this->securityService->validateTableName('products; DROP TABLE users'));
        $this->assertFalse($this->securityService->validateTableName('products/*'));
    }

    public function test_column_name_validation()
    {
        $this->assertTrue($this->securityService->validateColumnName('name'));
        $this->assertTrue($this->securityService->validateColumnName('user.id'));
        $this->assertFalse($this->securityService->validateColumnName('name; DROP TABLE'));
        $this->assertFalse($this->securityService->validateColumnName('name/*'));
    }

    public function test_array_sanitization()
    {
        $maliciousArray = [
            'name' => "'; DROP TABLE products; --",
            'description' => '<script>alert("XSS")</script>',
            'safe_field' => 'normal text',
        ];

        $sanitized = $this->securityService->sanitizeArray($maliciousArray);

        $this->assertStringNotContainsString('DROP TABLE', $sanitized['name']);
        $this->assertStringNotContainsString('<script>', $sanitized['description']);
        $this->assertEquals('normal text', $sanitized['safe_field']);
    }

    public function test_query_validation_rejects_dangerous_queries()
    {
        $this->assertFalse($this->securityService->validateQuery('DROP TABLE products'));
        $this->assertFalse($this->securityService->validateQuery('DELETE FROM users WHERE 1=1'));
        $this->assertFalse($this->securityService->validateQuery('SELECT * FROM users UNION SELECT * FROM admins'));
    }

    public function test_query_validation_accepts_safe_queries()
    {
        $this->assertTrue($this->securityService->validateQuery('SELECT * FROM products'));
        $this->assertTrue($this->securityService->validateQuery('SELECT name, price FROM products WHERE id = 1'));
    }
}
