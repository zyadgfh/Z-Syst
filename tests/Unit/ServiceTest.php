<?php

namespace Tests\Unit;

use App\Services\CacheService;
use App\Services\PageOptimizationService;
use App\Services\SecurityService;
use App\Services\XSSProtectionService;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    public function test_security_service_sanitize_input(): void
    {
        $service = new SecurityService;

        $malicious = ["name" => "'; DROP TABLE products; --"];
        $sanitized = $service->sanitizeInput($malicious);

        $this->assertNotEquals($malicious, $sanitized);
        $this->assertStringNotContainsString("'", $sanitized['name']);
        $this->assertStringContainsString('&#039;', $sanitized['name']);
    }

    public function test_xss_protection_service_sanitize_html(): void
    {
        $service = new XSSProtectionService;

        $xss = '<script>alert("XSS")</script>Hello';
        $sanitized = $service->sanitizeHTML($xss);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('Hello', $sanitized);
    }

    public function test_cache_service_remember(): void
    {
        $service = new CacheService;

        $key = 'test_key';
        $value = 'test_value';

        $result = $service->remember($key, 60, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);
    }

    public function test_page_optimization_service_minify_html(): void
    {
        $service = new PageOptimizationService;

        $html = '<div>  <p>Test</p>  </div>';
        $minified = $service->minifyHTML($html);

        $this->assertNotEquals($html, $minified);
        $this->assertStringNotContainsString('  ', $minified);
    }

    public function test_page_optimization_service_minify_css(): void
    {
        $service = new PageOptimizationService;

        $css = 'div { margin: 10px; }';
        $minified = $service->minifyCSS($css);

        $this->assertNotEquals($css, $minified);
    }

    public function test_page_optimization_service_minify_js(): void
    {
        $service = new PageOptimizationService;

        $js = 'function test() { // This is a comment
    return 1;
}';
        $minified = $service->minifyJS($js);

        $this->assertNotEquals($js, $minified);
        $this->assertStringNotContainsString('//', $minified);
        $this->assertStringNotContainsString("\n", $minified);
    }
}
