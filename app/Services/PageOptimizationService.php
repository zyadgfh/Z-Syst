<?php

namespace App\Services;

class PageOptimizationService
{
    /**
     * Minify HTML output
     */
    public function minifyHTML(string $html): string
    {
        // Remove comments
        $html = preg_replace('/<!--(.|\s)*?-->/', '', $html);

        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);

        // Remove whitespace at start/end
        $html = trim($html);

        return $html;
    }

    /**
     * Minify CSS output
     */
    public function minifyCSS(string $css): string
    {
        // Remove comments
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);

        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);

        // Remove unnecessary spaces
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);

        return trim($css);
    }

    /**
     * Minify JavaScript output
     */
    public function minifyJS(string $js): string
    {
        // Remove single-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);

        // Remove multi-line comments
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);

        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);

        return trim($js);
    }

    /**
     * Generate resource hints for performance
     */
    public function generateResourceHints(): array
    {
        return [
            'dns-prefetch' => [
                'https://fonts.googleapis.com',
                'https://fonts.gstatic.com',
            ],
            'preconnect' => [
                'https://fonts.googleapis.com',
                'https://fonts.gstatic.com',
            ],
            'preload' => [
                'fonts' => [
                    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
                ],
            ],
        ];
    }

    /**
     * Generate lazy loading attributes
     */
    public function generateLazyLoading(): string
    {
        return 'loading="lazy"';
    }

    /**
     * Optimize images (placeholder for actual implementation)
     */
    public function optimizeImage(string $imagePath): string
    {
        // This would integrate with an image optimization service
        // For now, return the original path
        return $imagePath;
    }

    /**
     * Generate critical CSS inline
     */
    public function generateCriticalCSS(): string
    {
        // Extract critical CSS for above-the-fold content
        // This would typically be built during deployment
        return '';
    }

    /**
     * Generate service worker registration
     */
    public function generateServiceWorker(): string
    {
        return <<<'JS'
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/service-worker.js')
            .then(function(registration) {
                console.log('ServiceWorker registered');
            })
            .catch(function(error) {
                console.log('ServiceWorker registration failed');
            });
    });
}
JS;
    }

    /**
     * Generate performance monitoring script
     */
    public function generatePerformanceMonitoring(): string
    {
        return <<<'JS'
window.addEventListener('load', function() {
    if ('performance' in window) {
        setTimeout(function() {
            var perfData = performance.timing;
            var pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
            var connectTime = perfData.responseEnd - perfData.requestStart;
            
            console.log('Page Load Time:', pageLoadTime + 'ms');
            console.log('Connection Time:', connectTime + 'ms');
            
            // Send to analytics if needed
            if (pageLoadTime > 3000) {
                console.warn('Slow page load detected');
            }
        }, 0);
    }
});
JS;
    }

    /**
     * Generate defer/async attributes for scripts
     */
    public function getScriptAttributes(string $scriptPath): string
    {
        // Defer non-critical scripts
        $criticalScripts = [
            '/js/app.js',
            '/js/chunk-vendors.js',
        ];

        if (in_array($scriptPath, $criticalScripts)) {
            return '';
        }

        return 'defer';
    }

    /**
     * Generate CDN asset URLs
     */
    public function getAssetURL(string $assetPath): string
    {
        $cdnUrl = config('app.asset_url');

        if ($cdnUrl) {
            return $cdnUrl.'/'.ltrim($assetPath, '/');
        }

        return asset($assetPath);
    }

    /**
     * Generate content security policy meta tag
     */
    public function generateCSPMeta(): string
    {
        $policies = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "img-src 'self' data: https:",
            "font-src 'self' https://fonts.gstatic.com",
            "connect-src 'self' https://api.example.com",
            "frame-src 'none'",
        ];

        return '<meta http-equiv="Content-Security-Policy" content="'.implode('; ', $policies).'">';
    }

    /**
     * Generate viewport meta tag for mobile optimization
     */
    public function generateViewportMeta(): string
    {
        return '<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">';
    }

    /**
     * Generate theme color meta tag
     */
    public function generateThemeColorMeta(): string
    {
        return '<meta name="theme-color" content="#ffffff">';
    }

    /**
     * Generate manifest link for PWA
     */
    public function generateManifestLink(): string
    {
        return '<link rel="manifest" href="/manifest.json">';
    }

    /**
     * Generate Apple touch icon links
     */
    public function generateAppleTouchIcons(): array
    {
        return [
            '<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">',
            '<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">',
            '<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">',
        ];
    }

    /**
     * Check if page load is slow
     */
    public function isSlowPageLoad(int $loadTimeMs): bool
    {
        return $loadTimeMs > 3000; // 3 seconds threshold
    }

    /**
     * Get page load recommendations
     */
    public function getLoadRecommendations(array $performanceData): array
    {
        $recommendations = [];

        if ($performanceData['image_size'] > 1000000) { // 1MB
            $recommendations[] = 'Optimize images - total size exceeds 1MB';
        }

        if ($performanceData['script_count'] > 10) {
            $recommendations[] = 'Reduce number of scripts - consider bundling';
        }

        if ($performanceData['css_size'] > 500000) { // 500KB
            $recommendations[] = 'Minify and compress CSS';
        }

        if ($performanceData['total_requests'] > 50) {
            $recommendations[] = 'Reduce HTTP requests - combine assets';
        }

        return $recommendations;
    }
}
