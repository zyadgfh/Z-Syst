<?php

namespace Tests\Unit\Services;

use App\Services\AI\FraudDetectionService;
use App\Models\Business;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraudDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FraudDetectionService $service;
    protected Business $business;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FraudDetectionService();
        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
    }

    public function test_analyze_sale_for_fraud()
    {
        $sale = Sale::factory()->create([
            'business_id' => $this->business->id,
            'totalAmount' => 100,
            'party_id' => Party::factory()->create(['business_id' => $this->business->id])->id,
        ]);

        $analysis = $this->service->analyzeSale($sale);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('sale_id', $analysis);
        $this->assertArrayHasKey('risk_level', $analysis);
        $this->assertArrayHasKey('risk_score', $analysis);
        $this->assertArrayHasKey('risk_factors', $analysis);
        $this->assertArrayHasKey('requires_review', $analysis);
        $this->assertArrayHasKey('recommended_action', $analysis);
    }

    public function test_analyze_sale_detects_unusual_amount()
    {
        $businessId = $this->business->id;

        // Create some previous sales to establish an average
        Sale::factory()->count(5)->create([
            'business_id' => $businessId,
            'totalAmount' => 100,
            'party_id' => Party::factory()->create(['business_id' => $businessId])->id,
        ]);

        // Now create a sale with unusually high amount
        $sale = Sale::factory()->create([
            'business_id' => $businessId,
            'totalAmount' => 10000, // 100x average
            'party_id' => Party::factory()->create(['business_id' => $businessId])->id,
        ]);

        $analysis = $this->service->analyzeSale($sale);

        $this->assertGreaterThan(0, $analysis['risk_score']);
        $this->assertContains('unusual_amount', array_column($analysis['risk_factors'], 'type'));
    }

    public function test_analyze_sale_detects_high_frequency()
    {
        $businessId = $this->business->id;
        $party = Party::factory()->create(['business_id' => $businessId]);

        // Create multiple sales in short time
        for ($i = 0; $i < 15; $i++) {
            Sale::factory()->create([
                'business_id' => $businessId,
                'party_id' => $party->id,
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $sale = Sale::where('business_id', $businessId)->latest()->first();
        $analysis = $this->service->analyzeSale($sale);

        $this->assertGreaterThan(0, $analysis['risk_score']);
    }

    public function test_analyze_sale_detects_unusual_time()
    {
        $businessId = $this->business->id;
        $sale = Sale::factory()->create([
            'business_id' => $businessId,
            'totalAmount' => 100,
            'created_at' => now()->setHour(3), // 3 AM - unusual time
            'party_id' => Party::factory()->create(['business_id' => $businessId])->id,
        ]);

        $analysis = $this->service->analyzeSale($sale);

        $this->assertGreaterThan(0, $analysis['risk_score']);
    }

    public function test_determine_risk_level()
    {
        $this->assertEquals('critical', $this->service->determineRiskLevel(100));
        $this->assertEquals('high', $this->service->determineRiskLevel(60));
        $this->assertEquals('medium', $this->service->determineRiskLevel(30));
        $this->assertEquals('low', $this->service->determineRiskLevel(10));
    }

    public function test_get_recommended_action()
    {
        $this->assertStringContainsString('Block transaction', $this->service->getRecommendedAction('critical'));
        $this->assertStringContainsString('Flag for manual review', $this->service->getRecommendedAction('high'));
        $this->assertStringContainsString('Flag for review', $this->service->getRecommendedAction('medium'));
        $this->assertStringContainsString('Process normally', $this->service->getRecommendedAction('low'));
    }

    public function test_analyze_purchase_for_fraud()
    {
        $businessId = $this->business->id;
        $purchase = Purchase::factory()->create([
            'business_id' => $businessId,
            'totalAmount' => 100,
            'party_id' => Party::factory()->create(['business_id' => $businessId])->id,
        ]);

        $analysis = $this->service->analyzePurchase($purchase);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('purchase_id', $analysis);
        $this->assertArrayHasKey('risk_level', $analysis);
        $this->assertArrayHasKey('risk_score', $analysis);
    }

    public function test_get_fraud_analytics()
    {
        $businessId = $this->business->id;

        // Create test sales
        for ($i = 0; $i < 10; $i++) {
            Sale::factory()->create([
                'business_id' => $businessId,
                'totalAmount' => 100 + ($i * 10),
                'party_id' => Party::factory()->create(['business_id' => $businessId])->id,
            ]);
        }

        $analytics = $this->service->getFraudAnalytics($businessId);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('total_sales_analyzed', $analytics);
        $this->assertArrayHasKey('high_risk_sales', $analytics);
        $this->assertArrayHasKey('risk_distribution', $analytics);
    }
}
