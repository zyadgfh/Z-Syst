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
            'invoiceNumber' => $purchase->invoiceNumber,
        ]);
    }

    public function test_purchase_has_fillable_attributes()
    {
        $fillable = (new Purchase)->getFillable();

        $this->assertContains('totalAmount', $fillable);
        $this->assertContains('paidAmount', $fillable);
        $this->assertContains('dueAmount', $fillable);
        $this->assertContains('isPaid', $fillable);
        $this->assertContains('party_id', $fillable);
        $this->assertContains('business_id', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('invoiceNumber', $fillable);
    }

    public function test_purchase_has_casts()
    {
        $casts = (new Purchase)->getCasts();

        $this->assertArrayHasKey('isPaid', $casts);
        $this->assertEquals('boolean', $casts['isPaid']);
        $this->assertArrayHasKey('totalAmount', $casts);
        $this->assertEquals('double', $casts['totalAmount']);
        $this->assertArrayHasKey('dueAmount', $casts);
        $this->assertEquals('double', $casts['dueAmount']);
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
        $paidPurchase = Purchase::factory()->create(['isPaid' => true, 'dueAmount' => 0]);
        $unpaidPurchase = Purchase::factory()->create(['isPaid' => false, 'dueAmount' => 100]);

        $this->assertTrue($paidPurchase->isPaid);
        $this->assertFalse($unpaidPurchase->isPaid);
    }
}
