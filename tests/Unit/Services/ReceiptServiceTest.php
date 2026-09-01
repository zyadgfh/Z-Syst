<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Receipt;
use App\Models\ReceiptSetting;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ReceiptServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReceiptService $service;
    protected Business $business;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        Auth::login($this->user);

        $this->service = new ReceiptService();
    }

    public function test_get_settings_returns_null_when_none_exist(): void
    {
        $settings = $this->service->getSettings($this->business->id);

        $this->assertNull($settings);
    }

    public function test_update_settings_creates_new(): void
    {
        $settings = $this->service->updateSettings($this->business->id, [
            'receipt_header' => 'Test Pharmacy',
            'receipt_footer' => 'Thank you!',
        ]);

        $this->assertDatabaseHas('receipt_settings', [
            'business_id' => $this->business->id,
            'receipt_header' => 'Test Pharmacy',
        ]);
    }

    public function test_update_settings_updates_existing(): void
    {
        ReceiptSetting::create([
            'business_id' => $this->business->id,
            'receipt_header' => 'Old Header',
            'is_active' => true,
        ]);

        $settings = $this->service->updateSettings($this->business->id, [
            'receipt_header' => 'New Header',
        ]);

        $this->assertEquals('New Header', $settings->receipt_header);
    }

    public function test_get_statistics_returns_empty(): void
    {
        $stats = $this->service->getStatistics($this->business->id);

        $this->assertEquals(0, $stats['total_receipts']);
        $this->assertEquals(0, $stats['sale_receipts']);
        $this->assertEquals(0, $stats['purchase_receipts']);
    }

    public function test_mark_as_printed(): void
    {
        $receipt = Receipt::create([
            'business_id' => $this->business->id,
            'receipt_number' => 'SALE-001',
            'type' => 'sale',
            'format' => 'pdf',
            'status' => 'generated',
            'user_id' => $this->user->id,
            'data' => ['type' => 'sale'],
        ]);

        $printed = $this->service->markAsPrinted($receipt);

        $this->assertEquals('printed', $printed->status);
    }
}
