<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_purchase()
    {
        $business = Business::factory()->create();
        $party = Party::factory()->create(['business_id' => $business->id]);
        $user = User::factory()->create(['business_id' => $business->id]);

        $purchase = Purchase::factory()->create([
            'business_id' => $business->id,
            'party_id' => $party->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'invoice_number' => $purchase->invoice_number,
        ]);
    }

    public function test_purchase_has_fillable_attributes()
    {
        $fillable = (new Purchase)->getFillable();

        $this->assertContains('total_amount', $fillable);
        $this->assertContains('paid_amount', $fillable);
        $this->assertContains('due_amount', $fillable);
        $this->assertContains('is_paid', $fillable);
        $this->assertContains('party_id', $fillable);
        $this->assertContains('business_id', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('invoice_number', $fillable);
    }

    public function test_purchase_has_casts()
    {
        $casts = (new Purchase)->getCasts();

        $this->assertArrayHasKey('is_paid', $casts);
        $this->assertEquals('boolean', $casts['is_paid']);
        $this->assertArrayHasKey('total_amount', $casts);
        $this->assertEquals('double', $casts['total_amount']);
        $this->assertArrayHasKey('due_amount', $casts);
        $this->assertEquals('double', $casts['due_amount']);
        $this->assertArrayHasKey('purchase_data', $casts);
        $this->assertEquals('json', $casts['purchase_data']);
    }

    public function test_purchase_belongs_to_party()
    {
        $party = Party::factory()->create();
        $purchase = Purchase::factory()->create(['party_id' => $party->id]);

        $this->assertInstanceOf(Party::class, $purchase->party);
        $this->assertEquals($party->id, $purchase->party->id);
    }

    public function test_purchase_belongs_to_user()
    {
        $user = User::factory()->create();
        $purchase = Purchase::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $purchase->user);
        $this->assertEquals($user->id, $purchase->user->id);
    }

    public function test_purchase_has_many_details()
    {
        $purchase = Purchase::factory()->create();
        PurchaseDetails::factory(2)->create(['purchase_id' => $purchase->id]);

        $this->assertCount(2, $purchase->details);
    }

    public function test_purchase_is_paid_flag_works()
    {
        $paidPurchase = Purchase::factory()->create(['is_paid' => true, 'due_amount' => 0]);
        $unpaidPurchase = Purchase::factory()->create(['is_paid' => false, 'due_amount' => 100]);

        $this->assertTrue($paidPurchase->is_paid);
        $this->assertFalse($unpaidPurchase->is_paid);
    }
}
