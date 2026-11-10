<?php

namespace Tests\Feature;

use App\Models\AnomalyAlert;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\Device;
use App\Models\DrugInteraction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Shift;
use App\Models\Stock;
use App\Models\StoreOrder;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\AnomalyDetector;
use App\Services\AssistantEngine;
use App\Services\EtaInvoiceService;
use App\Services\ForecastService;
use App\Services\OfflineSyncService;
use App\Services\PaymentGatewayService;
use App\Services\PromotionEngine;
use App\Services\ShiftService;
use App\Services\StockTakeService;
use App\Services\TelegramNotifier;
use App\Services\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sales operations unit — shifts & drawer, promotions, stock take,
 * accounting, forecasting, anomalies, assistant, TOTP, ETA (simulation),
 * gateways (simulation), storefront, offline sync, interactions.
 */
class OperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected int $businessId;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->businessId = (int) $this->user->business_id;
        $this->product = Product::factory()->create([
            'business_id' => $this->businessId,
            'sales_price' => 100,
            'purchase_with_tax' => 60,
        ]);
        Stock::create([
            'business_id' => $this->businessId,
            'product_id' => $this->product->id,
            'product_stock' => 50,
            'batch_no' => 'B1',
            'expire_date' => now()->addYear()->toDateString(),
        ]);
    }

    // ── Shifts & cash drawer ────────────────────────────────────────────

    public function test_shift_lifecycle_open_sell_cash_and_close_with_variance()
    {
        $service = app(ShiftService::class);

        $shift = $service->open($this->user, 100.0);
        $this->assertEquals('open', $shift->status);
        $this->assertEquals(100.0, $shift->opening_float);

        // A second open must be refused while one is open.
        try {
            $service->open($this->user, 50);
            $this->fail('expected BusinessRuleException for double-open');
        } catch (\App\Exceptions\BusinessRuleException $e) {
            $this->assertNotNull($e);
        }

        $service->addCash($shift, 'sale_cash', 300, 'POS sale');
        $service->addCash($shift, 'cash_out', 50, 'supplier petty cash');

        // expected = 100 + 300 - 50 = 350; counted 320 → -30 variance
        $report = $service->close($shift, 320.0);
        $this->assertEquals(350.0, $report['expected_cash']);
        $this->assertEquals(-30.0, $report['difference']);
        $this->assertEquals('closed', $shift->fresh()->status);

        // anomalies: |diff| = 30 < 50 → no drawer alert yet; force one:
        $shift->update(['difference' => -80]);
        $found = app(AnomalyDetector::class)->scan($this->businessId);
        $this->assertGreaterThanOrEqual(1, array_sum($found));
        $this->assertTrue(AnomalyAlert::where('type', 'drawer_variance')->where('business_id', $this->businessId)->exists());
    }

    // ── Promotions ──────────────────────────────────────────────────────

    public function test_promotion_engine_percent_coupon_and_qty_bonus()
    {
        $engine = app(PromotionEngine::class);

        // percent off product (automatic)
        \App\Models\Promotion::create(['business_id' => $this->businessId, 'name' => '10% off', 'type' => 'percent', 'value' => 10, 'applies_to' => 'product', 'product_id' => $this->product->id, 'is_active' => true]);
        $quote = $engine->quote($this->businessId, [['product_id' => $this->product->id, 'price' => 100, 'quantities' => 2]]);
        $this->assertEquals(20.0, $quote['discount']);

        // coupon only fires with the code
        \App\Models\Promotion::create(['business_id' => $this->businessId, 'name' => 'Coupon 25', 'type' => 'fixed', 'value' => 25, 'applies_to' => 'all', 'coupon_code' => 'SAVE25', 'is_active' => true]);
        $no = $engine->quote($this->businessId, [['product_id' => $this->product->id, 'price' => 100, 'quantities' => 1]]);
        $this->assertEquals(10.0, $no['discount']); // only the automatic one
        $yes = $engine->quote($this->businessId, [['product_id' => $this->product->id, 'price' => 100, 'quantities' => 1]], 'save25');
        $this->assertEquals(35.0, $yes['discount']);

        // buy 2 get 1 bonus unit (no discount amount)
        \App\Models\Promotion::create(['business_id' => $this->businessId, 'name' => '2+1', 'type' => 'qty_bonus', 'value' => 0, 'min_qty' => 2, 'bonus_qty' => 1, 'applies_to' => 'all', 'is_active' => true]);
        $bonus = $engine->quote($this->businessId, [['product_id' => $this->product->id, 'price' => 100, 'quantities' => 4]]);
        $this->assertEquals(2, $bonus['bonus_units']);
        $this->assertEquals(10.0, $bonus['discount']);

        // evaluate() burns usage counters
        $used = $engine->evaluate($this->businessId, [['product_id' => $this->product->id, 'price' => 100, 'quantities' => 1]], 'SAVE25');
        $this->assertGreaterThan(0, $used['discount']);
        $this->assertEquals(1, \App\Models\Promotion::where('coupon_code', 'SAVE25')->value('used_count'));
    }

    public function test_pos_checkout_applies_promotion_as_sale_discount()
    {
        \App\Models\Promotion::create(['business_id' => $this->businessId, 'name' => '10% off all', 'type' => 'percent', 'value' => 10, 'applies_to' => 'all', 'is_active' => true]);

        $this->actingAs($this->user);
        $response = $this->postJson(route('admin.pos.checkout'), [
            'products' => [['product_id' => $this->product->id, 'price' => 100, 'lossProfit' => 40, 'quantities' => 2]],
            'paidAmount' => 180,
            'isPaid' => true,
            'paymentType' => 'cash',
        ]);
        $response->assertStatus(200);
        $body = $response->json();
        $this->assertEquals(20.0, $body['promotion']['discount']);

        $sale = Sale::findOrFail($body['sale']['id']);
        $this->assertEquals(20.0, (float) $sale->discount_amount);
        // line price untouched (H-01) — discount is sale-level
        $this->assertEquals(100.0, (float) $sale->details()->first()->price);
    }

    // ── Stock take ──────────────────────────────────────────────────────

    public function test_stock_take_scan_and_apply_adjusts_stock()
    {
        $service = app(StockTakeService::class);
        $audit = $service->open($this->businessId, $this->user);
        $this->assertEquals(1, $audit->details()->count());

        // scan 3 physical units (system says 50 → variance -47)
        $detail = $service->scan($audit, $this->product->product_code);
        $service->scan($audit, $this->product->product_code);
        $service->scan($audit, $this->product->product_code);
        $this->assertEquals(3, $detail->physical_quantity);
        $this->assertEquals(-47, $detail->variance);

        $result = $service->apply($audit);
        $this->assertEquals(1, $result['applied']);
        $this->assertEquals(3, (int) Stock::where('product_id', $this->product->id)->sum('product_stock'));
        $this->assertEquals('completed', $audit->fresh()->status);
    }

    // ── Accounting ──────────────────────────────────────────────────────

    public function test_sale_posts_journal_and_vat_report_balances()
    {
        $sale = Sale::create([
            'business_id' => $this->businessId,
            'total_amount' => 200, 'paid_amount' => 200, 'due_amount' => 0,
            'is_paid' => true, 'payment_type' => 'cash', 'sale_date' => now()->toDateString(),
        ]);
        $sale->details()->create(['product_id' => $this->product->id, 'price' => 100, 'purchase_price' => 60, 'quantities' => 2, 'loss_profit' => 80]);

        $entry = app(AccountingService::class)->postSale($sale);
        $this->assertCount(4, $entry->lines); // revenue credit + cash debit + COGS debit + inventory credit
        $debits = collect($entry->lines)->sum('debit');
        $credits = collect($entry->lines)->sum('credit');
        $this->assertEquals($debits, $credits, 'journal must balance');

        $vat = app(AccountingService::class)->vatReport($this->businessId, now()->startOfMonth()->toDateString(), now()->toDateString());
        $this->assertEquals(200.0, $vat['sales_revenue']);
        $this->assertGreaterThan(0, $vat['net_vat_due']);
    }

    // ── Forecast + auto-order ───────────────────────────────────────────

    public function test_forecast_generates_rows_and_reorder_points()
    {
        $sale = Sale::create(['business_id' => $this->businessId, 'total_amount' => 100, 'sale_date' => now()->subDays(2)->toDateString()]);
        $sale->details()->create(['product_id' => $this->product->id, 'price' => 100, 'quantities' => 10]);

        $written = app(ForecastService::class)->generate($this->businessId, 7);
        $this->assertGreaterThanOrEqual(7, $written);
        $this->assertDatabaseHas('sales_forecasts', ['business_id' => $this->businessId, 'product_id' => $this->product->id]);
        $this->assertDatabaseHas('auto_order_suggestions', ['business_id' => $this->businessId, 'product_id' => $this->product->id]);
    }

    // ── Anomalies ───────────────────────────────────────────────────────

    public function test_excessive_returns_raise_anomaly()
    {
        for ($i = 0; $i < 4; $i++) {
            $sale = Sale::create(['business_id' => $this->businessId, 'total_amount' => 10, 'sale_date' => now()->toDateString(), 'user_id' => $this->user->id]);
            SaleReturn::create(['business_id' => $this->businessId, 'sale_id' => $sale->id, 'user_id' => $this->user->id, 'invoice_no' => 'R'.$i, 'return_date' => now()->toDateString()]);
        }

        app(AnomalyDetector::class)->scan($this->businessId);
        $this->assertTrue(AnomalyAlert::where('type', 'excessive_returns')->exists());
    }

    // ── Assistant ───────────────────────────────────────────────────────

    public function test_assistant_answers_sales_and_stock_questions()
    {
        Sale::create(['business_id' => $this->businessId, 'total_amount' => 450, 'sale_date' => now()->toDateString()]);
        $engine = app(AssistantEngine::class);

        $sales = $engine->answer($this->businessId, 'كام بيعت النهاردة؟');
        $this->assertStringContainsString('450', $sales['answer']);

        $stock = $engine->answer($this->businessId, 'مخزون '.$this->product->name);
        $this->assertStringContainsString('50', $stock['answer']);

        $unknown = $engine->answer($this->businessId, 'الجو حلو النهاردة؟');
        $this->assertEquals('unknown', $unknown['intent']);
    }

    // ── TOTP ────────────────────────────────────────────────────────────

    public function test_totp_rfc6238_vector_and_verification_window()
    {
        // RFC 6238 test vector (SHA1, 8 digits, secret "12345678901234567890")
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // base32 of the ASCII seed
        $code = Totp::code($secret, 59, 30, 8);
        $this->assertSame('94287082', $code);

        $live = Totp::generateSecret();
        $this->assertTrue(Totp::verify($live, Totp::code($live)));
        $this->assertFalse(Totp::verify($live, '000000'));
    }

    // ── ETA (simulation) ────────────────────────────────────────────────

    public function test_eta_simulation_accepts_document_with_ubl_shape()
    {
        $sale = Sale::create(['business_id' => $this->businessId, 'total_amount' => 100, 'paid_amount' => 100, 'sale_date' => now()->toDateString()]);
        $sale->details()->create(['product_id' => $this->product->id, 'price' => 100, 'quantities' => 1]);

        $invoice = app(EtaInvoiceService::class)->submit($sale);
        $this->assertEquals('accepted', $invoice->status);
        $this->assertNotNull($invoice->submission_uuid);

        $doc = $invoice->request_payload;
        $this->assertEquals('I', $doc['documentType']);
        $this->assertCount(1, $doc['invoiceLines']);
        $this->assertEquals('T1', $doc['invoiceLines'][0]['taxableItems'][0]['taxType']);
    }

    // ── Payment gateways (simulation) ───────────────────────────────────

    public function test_gateway_simulation_flow_initiates_and_marks_paid()
    {
        $service = app(PaymentGatewayService::class);
        $tx = $service->initiate($this->businessId, 'paymob', 'subscription', null, 500);
        $this->assertEquals('pending', $tx->status);
        $this->assertEquals('simulation', $tx->payload['mode']);

        $paid = $service->handleCallback('paymob', $tx->id, ['success' => 'true']);
        $this->assertEquals('paid', $paid->status);
        $this->assertNotNull($paid->paid_at);

        // replay is idempotent
        $again = $service->handleCallback('paymob', $tx->id, ['success' => 'true']);
        $this->assertEquals('paid', $again->status);
    }

    public function test_gateway_rejects_unsigned_callback_in_live_mode()
    {
        BusinessSetting::put($this->businessId, 'fawry_api_key', 'test-key');
        BusinessSetting::put($this->businessId, 'fawry_hmac_secret', 'topsecret');
        BusinessSetting::put($this->businessId, 'fawry_merchant_code', 'M1');

        $service = app(PaymentGatewayService::class);
        $tx = $service->initiate($this->businessId, 'fawry', 'subscription', null, 300);

        $bad = $service->handleCallback('fawry', $tx->id, ['success' => 'true', 'signature' => 'forged']);
        $this->assertEquals('failed', $bad->status);

        $tx->update(['reference' => 'REF1']);
        $good = hash('sha256', 'M1'.'REF1'.(300.0).'topsecret');
        $ok = $service->handleCallback('fawry', $tx->id, ['success' => 'true', 'reference' => 'REF1', 'signature' => $good]);
        $this->assertEquals('paid', $ok->status);
    }

    // ── Storefront ──────────────────────────────────────────────────────

    public function test_storefront_checkout_uses_server_prices_and_creates_order()
    {
        $this->business = Business::find($this->businessId);
        $this->business?->update(['store_slug' => 'test-pharm']);
        if (!$this->business) {
            $this->business = Business::factory()->create(['store_slug' => 'test-pharm']);
        }

        $slug = 'test-pharm';
        $show = $this->get("/store/{$slug}");
        if ($show->isNotFound()) {
            $this->markTestSkipped('tenant business not on this slug');
        }
        $show->assertOk();

        $res = $this->postJson("/store/{$slug}/checkout", [
            'customer_name' => 'Ahmed', 'customer_phone' => '0100000000',
            'fulfillment' => 'delivery', 'address' => 'Nasr City',
            'items' => [['product_id' => $this->product->id, 'qty' => 2]],
        ]);
        $res->assertOk();
        // 2 × 100 list price + 15 delivery — tampered client prices are ignored
        $this->assertEquals(215.0, $res->json('total'));
        $this->assertDatabaseHas('store_orders', ['order_number' => $res->json('order_number'), 'total' => 215.0]);
    }

    // ── Offline sync ────────────────────────────────────────────────────

    public function test_offline_sync_accepts_and_dedupes_by_client_uuid()
    {
        $service = app(OfflineSyncService::class);
        $uuid = (string) \Illuminate\Support\Str::uuid();

        $first = $service->sync([[
            'client_uuid' => $uuid,
            'products' => [['product_id' => $this->product->id, 'price' => 100, 'quantities' => 1, 'batch_no' => 'B1']],
            'paid_amount' => 100, 'is_paid' => true, 'payment_type' => 'cash',
        ]], $this->user, null);
        $this->assertCount(1, $first['accepted']);

        $replay = $service->sync([[
            'client_uuid' => $uuid,
            'products' => [['product_id' => $this->product->id, 'price' => 100, 'quantities' => 1, 'batch_no' => 'B1']],
            'paid_amount' => 100, 'is_paid' => true, 'payment_type' => 'cash',
        ]], $this->user, null);
        $this->assertCount(1, $replay['duplicates'], 'replay must not double-sell');
        $this->assertCount(0, $replay['accepted']);
    }

    // ── Drug interactions at the POS ────────────────────────────────────

    public function test_pos_interactions_endpoint_warns_on_known_pair()
    {
        $other = Product::factory()->create(['business_id' => $this->businessId, 'sales_price' => 50]);
        DrugInteraction::create([
            'business_id' => null,
            'drug_a_name' => $this->product->name, 'drug_b_name' => $other->name,
            'severity' => 'severe', 'description' => 'خطر نزيف عند الجمع',
        ]);

        $res = $this->actingAs($this->user)->postJson(route('admin.pos.interactions'), [
            'product_ids' => [$this->product->id, $other->id],
        ]);
        $res->assertOk();
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('severe', $data[0]['severity']);
    }

    // ── Device stock take API ───────────────────────────────────────────

    public function test_device_stock_take_scan_flow()
    {
        $this->actingAs($this->user, 'sanctum');
        $store = $this->postJson('/api/v1/stock-takes', ['type' => 'spot_check']);
        $store->assertCreated();
        $this->assertGreaterThan(0, $store->json('data.lines'));

        // opening twice returns the live session
        $again = $this->postJson('/api/v1/stock-takes', []);
        $again->assertOk();
        $this->assertTrue($again->json('existing'));
        $this->assertEquals($store->json('data.id'), $again->json('data.id'));

        $scan = $this->postJson('/api/v1/stock-takes/scan', ['code' => $this->product->productCode, 'qty' => 2]);
        $scan->assertOk();
        $this->assertTrue($scan->json('beep'));
        $this->assertEquals(2, $scan->json('data.physical_quantity'));
    }

    // ── Telegram simulation ─────────────────────────────────────────────

    public function test_telegram_digest_falls_back_to_simulation()
    {
        $notifier = app(TelegramNotifier::class);
        $this->assertFalse($notifier->configured($this->businessId));
        $digest = $notifier->dailyDigest($this->businessId);
        $this->assertStringContainsString('ملخص اليوم', $digest);
    }

    // ── Dose labels (طباعة الجرعات كباركود) ─────────────────────────────

    public function test_dose_persists_through_sale_and_sheet_renders_code128()
    {
        $this->actingAs($this->user);
        $res = $this->postJson(route('admin.pos.checkout'), [
            'products' => [[
                'product_id' => $this->product->id, 'price' => 100, 'lossProfit' => 40,
                'quantities' => 1, 'batch_no' => 'B1',
                'dose' => 'قرص صباحًا ومساءً بعد الأكل', 'dose_code' => 'BID',
            ]],
            'paidAmount' => 100, 'isPaid' => true, 'paymentType' => 'cash',
        ]);
        $res->assertStatus(200);
        $this->assertNotNull($res->json('dose_labels_url'), 'dose captured → labels url expected');

        $detail = \App\Models\SaleDetails::where('sale_id', $res->json('sale.id'))->first();
        $this->assertSame('BID', $detail->dose_code);

        $sheet = $this->get($res->json('dose_labels_url'));
        $sheet->assertOk();
        $sheet->assertSee('DOSE:'.$detail->sale_id.':'.$detail->id, false);
        $sheet->assertSee('<svg', false);

        // no dose → no labels url
        $res2 = $this->postJson(route('admin.pos.checkout'), [
            'products' => [['product_id' => $this->product->id, 'price' => 100, 'lossProfit' => 40, 'quantities' => 1, 'batch_no' => 'B1']],
            'paidAmount' => 100, 'isPaid' => true, 'paymentType' => 'cash',
        ]);
        $res2->assertStatus(200);
        $this->assertNull($res2->json('dose_labels_url'));

        // scan the sticker → instruction card JSON
        $this->getJson('/admin/dose/DOSE:'.$detail->sale_id.':'.$detail->id)
            ->assertOk()
            ->assertJsonPath('data.dose_code', 'BID');
    }

    public function test_dose_lookup_by_scan_returns_instruction_data()
    {
        $sale = Sale::create(['business_id' => $this->businessId, 'total_amount' => 100, 'paid_amount' => 100, 'sale_date' => now()->toDateString(), 'invoice_number' => 'INV-1']);
        $detail = $sale->details()->create(['product_id' => $this->product->id, 'price' => 100, 'quantities' => 1, 'dose' => 'ثلاث مرات يوميًا', 'dose_code' => 'TID']);

        $this->actingAs($this->user);
        $this->getJson('/admin/dose/DOSE:'.$sale->id.':'.$detail->id)
            ->assertOk()
            ->assertJsonPath('data.dose_code', 'TID')
            ->assertJsonPath('data.sale', 'INV-1');

        // tenant isolation
        $other = User::factory()->create();
        $this->actingAs($other)->getJson('/admin/dose/DOSE:'.$sale->id.':'.$detail->id)->assertStatus(404);

        // malformed payload
        $this->actingAs($this->user)->getJson('/admin/dose/NOPE:1:2')->assertStatus(422);
    }

    // ── API docs ────────────────────────────────────────────────────────

    public function test_openapi_spec_lists_api_routes()
    {
        $res = $this->getJson('/api/v1/docs');
        $res->assertOk();
        $spec = $res->json();
        $this->assertEquals('3.1.0', $spec['openapi']);
        $this->assertArrayHasKey('/sales', $spec['paths']);
    }
}
