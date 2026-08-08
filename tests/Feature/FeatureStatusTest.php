<?php

namespace Tests\Feature;

use App\Services\FeatureStatusService;
use Tests\TestCase;

class FeatureStatusTest extends TestCase
{
    public function test_feature_status_service_reports_completed_modules(): void
    {
        $service = new FeatureStatusService;
        $features = $service->getFeatures();

        $this->assertIsArray($features);
        $this->assertGreaterThan(0, count($features));

        $keys = array_column($features, 'key');
        $this->assertContains('multi_tenancy', $keys);
        $this->assertContains('backup_automation', $keys);
        $this->assertContains('sales_invoices', $keys);
        $this->assertContains('reports_statistics', $keys);
    }
}
