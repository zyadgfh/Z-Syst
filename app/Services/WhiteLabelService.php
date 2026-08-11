<?php

namespace App\Services;

use App\Models\Business;
use App\Models\WhiteLabelConfiguration;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Facades\Storage;

class WhiteLabelService
{
    use WithTransactionalOperations;

    /**
     * Get white-label configuration for a business.
     *
     * @param int $businessId
     * @return array
     */
    public function getConfiguration(int $businessId): array
    {
        $config = WhiteLabelConfiguration::where('business_id', $businessId)->first();

        if (!$config) {
            return $this->getDefaultConfiguration($businessId);
        }

        return [
            'business_id' => $businessId,
            'brand_name' => $config->brand_name,
            'logo_url' => $config->logo_url,
            'primary_color' => $config->primary_color,
            'secondary_color' => $config->secondary_color,
            'accent_color' => $config->accent_color,
            'font_family' => $config->font_family,
            'custom_domain' => $config->custom_domain,
            'custom_email_from' => $config->custom_email_from,
            'custom_email_name' => $config->custom_email_name,
            'favicon_url' => $config->favicon_url,
            'custom_css' => $config->custom_css,
            'hide_branding' => $config->hide_branding ?? false,
        ];
    }

    /**
     * Update white-label configuration.
     *
     * @param int $businessId
     * @param array<string, mixed> $data
     * @return array
     */
    public function updateConfiguration(int $businessId, array $data): array
    {
        return $this->executeTransaction(function () use ($businessId, $data) {
            $config = WhiteLabelConfiguration::updateOrCreate(
                ['business_id' => $businessId],
                [
                    'brand_name' => $data['brand_name'] ?? null,
                    'primary_color' => $data['primary_color'] ?? '#007bff',
                    'secondary_color' => $data['secondary_color'] ?? '#6c757d',
                    'accent_color' => $data['accent_color'] ?? '#28a745',
                    'font_family' => $data['font_family'] ?? 'Inter',
                    'custom_domain' => $data['custom_domain'] ?? null,
                    'custom_email_from' => $data['custom_email_from'] ?? null,
                    'custom_email_name' => $data['custom_email_name'] ?? null,
                    'hide_branding' => $data['hide_branding'] ?? false,
                    'custom_css' => $data['custom_css'] ?? null,
                ]
            );

            // Handle logo upload
            if (isset($data['logo']) && $data['logo']) {
                $logoPath = $data['logo']->store('white-label/logos', 'public');
                $config->update(['logo_url' => asset('storage/' . $logoPath)]);
            }

            // Handle favicon upload
            if (isset($data['favicon']) && $data['favicon']) {
                $faviconPath = $data['favicon']->store('white-label/favicons', 'public');
                $config->update(['favicon_url' => asset('storage/' . $faviconPath)]);
            }

            return [
                'success' => true,
                'message' => 'White-label configuration updated',
                'configuration' => $this->getConfiguration($businessId),
            ];
        });
    }

    /**
     * Get default configuration.
     *
     * @param int $businessId
     * @return array
     */
    protected function getDefaultConfiguration(int $businessId): array
    {
        $business = Business::findOrFail($businessId);

        return [
            'business_id' => $businessId,
            'brand_name' => $business->companyName,
            'logo_url' => null,
            'primary_color' => '#007bff',
            'secondary_color' => '#6c757d',
            'accent_color' => '#28a745',
            'font_family' => 'Inter',
            'custom_domain' => null,
            'custom_email_from' => null,
            'custom_email_name' => null,
            'favicon_url' => null,
            'custom_css' => null,
            'hide_branding' => false,
        ];
    }

    /**
     * Generate custom CSS for white-label configuration.
     *
     * @param int $businessId
     * @return string
     */
    public function generateCustomCSS(int $businessId): string
    {
        $config = $this->getConfiguration($businessId);

        $css = ":root {\n";
        $css .= "  --primary-color: {$config['primary_color']};\n";
        $css .= "  --secondary-color: {$config['secondary_color']};\n";
        $css .= "  --accent-color: {$config['accent_color']};\n";
        $css .= "  --font-family: '{$config['font_family']}', sans-serif;\n";
        $css .= "}\n\n";

        if ($config['custom_css']) {
            $css .= $config['custom_css'] . "\n";
        }

        return $css;
    }

    /**
     * Validate custom domain.
     *
     * @param string $domain
     * @return array
     */
    public function validateCustomDomain(string $domain): array
    {
        // Check if domain is valid
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            return [
                'valid' => false,
                'message' => 'Invalid domain format',
            ];
        }

        // Check if domain is available (would integrate with DNS service)
        // For now, simulate the check

        return [
            'valid' => true,
            'message' => 'Domain is available',
        ];
    }

    /**
     * Get email template with white-label customization.
     *
     * @param int $businessId
     * @param string $templateName
     * @return array
     */
    public function getEmailTemplate(int $businessId, string $templateName): array
    {
        $config = $this->getConfiguration($businessId);

        return [
            'from_email' => $config['custom_email_from'] ?? 'noreply@default.com',
            'from_name' => $config['custom_email_name'] ?? $config['brand_name'],
            'brand_name' => $config['brand_name'],
            'logo_url' => $config['logo_url'],
            'primary_color' => $config['primary_color'],
        ];
    }
}