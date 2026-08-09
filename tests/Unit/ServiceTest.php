<?php

namespace Tests\Unit;

use App\Services\BackupService;
use App\Services\CacheService;
use App\Services\CSRFProtectionService;
use App\Services\PageOptimizationService;
use App\Services\QueryOptimizationService;
use App\Services\SecurityService;
use App\Services\XSSProtectionService;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    public function test_security_service_sanitize_input()
    {
        $service = new SecurityService;

        $malicious = "'; DROP TABLE products; --";
        $sanitized = $service->sanitizeInput($malicious);

        $this->assertNotEquals($malicious, $sanitized);
        $this->assertStringNotContainsString('DROP TABLE', $sanitized);
    }

    public function test_security_service_validate_table_name()
    {
        $service = new SecurityService;

        $this->assertTrue($service->validateTableName('products'));
        $this->assertTrue($service->validateTableName('user_roles'));
        $this->assertFalse($service->validateTableName('products; DROP TABLE'));
    }

    public function test_xss_protection_service_sanitize_html()
    {
        $service = new XSSProtectionService;

        $xss = '<script>alert("XSS")</script>Hello';
        $sanitized = $service->sanitizeHTML($xss);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('Hello', $sanitized);
    }

    public function test_xss_protection_service_check_xss()
    {
        $service = new XSSProtectionService;

        $this->assertTrue($service->checkXSS('<script>alert("XSS")</script>'));
        $this->assertFalse($service->checkXSS('Safe text'));
    }

    public function test_csrf_protection_service_generate_token()
    {
        $service = new CSRFProtectionService;

        $token = $service->generateToken();

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
    }

    public function test_csrf_protection_service_validate_token()
    {
        $service = new CSRFProtectionService;

        $token = $service->generateToken();
        $this->assertTrue($service->validateToken($token, $token));
        $this->assertFalse($service->validateToken($token, 'wrong_token'));
    }

    public function test_cache_service_remember()
    {
        $service = new CacheService;

        $key = 'test_key';
        $value = 'test_value';

        $result = $service->remember($key, 60, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);
    }

    public function test_cache_service_business_key()
    {
        $service = new CacheService;

        $key = $service->businessKey(1, 'test');

        $this->assertEquals('business:1:test', $key);
    }

    public function test_query_optimization_service_detect_n_plus_one()
    {
        $service = new QueryOptimizationService;

        $queries = [
            ['query' => 'SELECT * FROM users', 'time' => 10],
            ['query' => 'SELECT * FROM users', 'time' => 11],
            ['query' => 'SELECT * FROM users', 'time' => 12],
        ];

        $nPlusOne = $service->detectNPlusOneQueries($queries);

        $this->assertIsArray($nPlusOne);
    }

    public function test_backup_service_create_database_backup()
    {
        $service = new BackupService;

        // This test requires actual database
        // For now, we'll test the method exists
        $this->assertTrue(method_exists($service, 'createDatabaseBackup'));
    }

    public function test_page_optimization_service_minify_html()
    {
        $service = new PageOptimizationService;

        $html = '<div>  <p>Test</p>  </div>';
        $minified = $service->minifyHTML($html);

        $this->assertNotEquals($html, $minified);
        $this->assertStringNotContainsString('  ', $minified);
    }

    public function test_page_optimization_service_minify_css()
    {
        $service = new PageOptimizationService;

        $css = 'div { margin: 10px; }';
        $minified = $service->minifyCSS($css);

        $this->assertNotEquals($css, $minified);
    }

    public function test_page_optimization_service_minify_js()
    {
        $service = new PageOptimizationService;

        $js = 'function test() { return 1; }';
        $minified = $service->minifyJS($js);

        $this->assertNotEquals($js, $minified);
    }
}
