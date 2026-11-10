<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\DueCollect;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Due collections were the one flow where the application and the live Supabase
 * schema genuinely disagreed, so both `POST /api/v1/dues` and the dues report
 * failed outright and nothing covered them. These tests pin the unified
 * snake_case schema (amount / paid_amount / due_amount / payment_date), the
 * camelCase compatibility contract the installed Flutter build still relies on,
 * and the tenant boundary on every dues read path.
 */
class DueCollectionTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Party $party;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create(['remaining_shop_balance' => 0]);
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->party = Party::factory()->create([
            'business_id' => $this->business->id,
            'type' => 'customer',
            'opening_balance' => 500,
            'due' => 300,
        ]);
    }

    /**
     * The mobile app posts the legacy camelCase keys; NormalizeLegacyPayloadKeys
     * maps them onto the canonical columns.
     */
    public function test_can_collect_due_using_legacy_camel_case_payload()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/dues', [
            'party_id' => $this->party->id,
            'paymentType' => 'Cash',
            'paymentDate' => '2026-11-09',
            'payDueAmount' => 120,
        ]);

        $response->assertStatus(200);

        $row = DueCollect::withoutGlobalScopes()->firstOrFail();

        $this->assertSame($this->business->id, (int) $row->business_id);
        $this->assertSame($this->party->id, (int) $row->party_id);
        $this->assertEquals(300.0, (float) $row->amount, 'total due at collection time');
        $this->assertEquals(120.0, (float) $row->paid_amount, 'amount collected now');
        $this->assertEquals(180.0, (float) $row->due_amount, 'remaining after payment');
        $this->assertSame('Cash', $row->payment_type);
        $this->assertNotNull($row->payment_date);
        $this->assertNotNull($row->invoice_number, 'invoice number is generated on create');

        // The party's balance and the invoice counters move with the payment.
        $this->assertEquals(180.0, (float) $this->party->fresh()->due);
        $this->assertEquals(380.0, (float) $this->party->fresh()->opening_balance);
    }

    /**
     * The response carries both the canonical snake_case columns and the legacy
     * camelCase aliases, so an already-installed build keeps rendering.
     */
    public function test_response_exposes_both_canonical_and_legacy_keys()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/dues', [
            'party_id' => $this->party->id,
            'payment_type' => 'Cash',
            'payment_date' => '2026-11-09',
            'paid_amount' => 120,
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');

        $this->assertEquals(120.0, (float) $data['paid_amount']);
        $this->assertEquals(120.0, (float) $data['payDueAmount']);
        $this->assertEquals(300.0, (float) $data['amount']);
        $this->assertEquals(300.0, (float) $data['totalDue']);
        $this->assertEquals(180.0, (float) $data['due_amount']);
        $this->assertEquals(180.0, (float) $data['dueAmountAfterPay']);
        $this->assertSame($data['payment_type'], $data['paymentType']);
        $this->assertSame($data['invoice_number'], $data['invoiceNumber']);
    }

    public function test_collecting_more_than_the_opening_balance_is_rejected()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/dues', [
            'party_id' => $this->party->id,
            'payment_type' => 'Cash',
            'payment_date' => '2026-11-09',
            'paid_amount' => 900,
        ]);

        $this->assertNotSame(200, $response->status());
        $this->assertSame(0, DueCollect::withoutGlobalScopes()->count());
    }

    /**
     * Tenant and actor always come from the session: a client-supplied
     * business_id/user_id must not be able to plant a row in another tenant.
     */
    public function test_client_cannot_spoof_the_tenant_on_create()
    {
        $otherBusiness = Business::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/v1/dues', [
            'party_id' => $this->party->id,
            'business_id' => $otherBusiness->id,
            'user_id' => 'not-the-authenticated-user',
            'payment_type' => 'Cash',
            'payment_date' => '2026-11-09',
            'paid_amount' => 50,
        ]);

        // tenant.check rejects a foreign business_id outright; either way the
        // row must never be attributed to the other business.
        $this->assertSame(0, DueCollect::withoutGlobalScopes()
            ->where('business_id', $otherBusiness->id)->count());

        if ($response->status() === 200) {
            $this->assertSame(
                $this->business->id,
                (int) DueCollect::withoutGlobalScopes()->firstOrFail()->business_id
            );
            $this->assertSame(
                (string) $this->user->id,
                (string) DueCollect::withoutGlobalScopes()->firstOrFail()->user_id
            );
        }
    }

    /**
     * Regression: the search filters on both dues endpoints used an ungrouped
     * orWhere chain, which binds looser than the tenant condition appended
     * afterwards — a search term matched other businesses' rows.
     */
    public function test_dues_index_search_does_not_leak_other_tenants()
    {
        $otherBusiness = Business::factory()->create();
        $otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);
        $otherParty = Party::factory()->create([
            'business_id' => $otherBusiness->id,
            'type' => 'customer',
            'opening_balance' => 500,
            'due' => 300,
            'name' => 'Leaky Pharmacy',
        ]);

        foreach ([[$this->user, $this->party], [$otherUser, $otherParty]] as [$actor, $target]) {
            $this->actingAs($actor)->postJson('/api/v1/dues', [
                'party_id' => $target->id,
                'payment_type' => 'Cash',
                'payment_date' => '2026-11-09',
                'paid_amount' => 100,
            ])->assertStatus(200);
        }

        $mine = $this->actingAs($this->user)->getJson('/api/v1/dues?search=D-')->assertStatus(200);
        foreach ($mine->json('data.data') as $row) {
            $this->assertSame($this->business->id, (int) $row['business_id']);
        }

        $list = $this->actingAs($this->user)->getJson('/api/v1/dues-list?search=a')->assertStatus(200);
        foreach ($list->json('data.data') as $row) {
            $this->assertNotSame($otherParty->id, (int) $row['id']);
        }
    }

    /**
     * The dues report was a 500 before the schema was unified.
     */
    public function test_due_collects_report_returns_totals()
    {
        $this->actingAs($this->user)->postJson('/api/v1/dues', [
            'party_id' => $this->party->id,
            'payment_type' => 'Cash',
            'payment_date' => '2026-11-09',
            'paid_amount' => 120,
        ])->assertStatus(200);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/due-collects-report')
            ->assertStatus(200);

        $this->assertEquals(120.0, (float) $response->json('total_paid'));
        $this->assertEquals(300.0, (float) $response->json('total_due'));
        $this->assertCount(1, $response->json('data.data'));
    }
}
