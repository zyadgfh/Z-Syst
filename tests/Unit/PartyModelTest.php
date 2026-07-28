<?php

namespace Tests\Unit;

use App\Models\Party;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_party()
    {
        $business = Business::factory()->create();

        $party = Party::factory()->create([
            'business_id' => $business->id,
        ]);

        $this->assertDatabaseHas('parties', [
            'id' => $party->id,
            'name' => $party->name,
        ]);
    }

    public function test_party_has_fillable_attributes()
    {
        $fillable = (new Party())->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('business_id', $fillable);
        $this->assertContains('opening_balance', $fillable);
        $this->assertContains('due', $fillable);
    }

    public function test_party_has_due_cast()
    {
        $casts = (new Party())->getCasts();

        $this->assertArrayHasKey('due', $casts);
        $this->assertEquals('double', $casts['due']);
    }

    public function test_party_has_sales_dues_relation()
    {
        $party = Party::factory()->create();
        $sale = Sale::factory()->create([
            'party_id' => $party->id,
            'dueAmount' => 100,
        ]);

        $this->assertCount(1, $party->sales_dues);
        $this->assertInstanceOf(Sale::class, $party->sales_dues->first());
    }

    public function test_party_has_purchases_dues_relation()
    {
        $party = Party::factory()->create();
        $purchase = Purchase::factory()->create([
            'party_id' => $party->id,
            'dueAmount' => 200,
        ]);

        $this->assertCount(1, $party->purchases_dues);
        $this->assertInstanceOf(Purchase::class, $party->purchases_dues->first());
    }

    public function test_party_sales_dues_excludes_zero_due()
    {
        $party = Party::factory()->create();
        Sale::factory()->create([
            'party_id' => $party->id,
            'dueAmount' => 0,
            'isPaid' => true,
        ]);

        $this->assertCount(0, $party->sales_dues);
    }

    public function test_party_can_be_deleted()
    {
        $party = Party::factory()->create();
        $partyId = $party->id;

        $party->delete();

        $this->assertDatabaseMissing('parties', ['id' => $partyId]);
    }
}