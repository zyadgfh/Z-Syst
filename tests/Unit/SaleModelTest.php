<?php

namespace Tests\Unit;

use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Party;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_sale()
    {
        $business = Business::factory()->create();
        $party = Party::factory()->create(['business_id' => $business->id]);
        $user = User::factory()->create(['business_id' => $business->id]);

        $sale = Sale::factory()->create([
            'business_id' => $business->id,
            'party_id' => $party->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'invoiceNumber' => $sale->invoiceNumber,
        ]);
    }

    public function test_sale_has_fillable_attributes()
    {
        $fillable = (new Sale())->getFillable();

        $this->assertContains('totalAmount', $fillable);
        $this->assertContains('paidAmount', $fillable);
        $this->assertContains('dueAmount', $fillable);
        $this->assertContains('isPaid', $fillable);
        $this->assertContains('party_id', $fillable);
        $this->assertContains('business_id', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('invoiceNumber', $fillable);
    }

    public function test_sale_has_casts()
    {
        $casts = (new Sale())->getCasts();

        $this->assertArrayHasKey('isPaid', $casts);
        $this->assertEquals('boolean', $casts['isPaid']);
        $this->assertArrayHasKey('totalAmount', $casts);
        $this->assertEquals('double', $casts['totalAmount']);
        $this->assertArrayHasKey('dueAmount', $casts);
        $this->assertEquals('double', $casts['dueAmount']);
        $this->assertArrayHasKey('meta', $casts);
        $this->assertEquals('json', $casts['meta']);
    }

    public function test_sale_belongs_to_party()
    {
        $party = Party::factory()->create();
        $sale = Sale::factory()->create(['party_id' => $party->id]);

        $this->assertInstanceOf(Party::class, $sale->party);
        $this->assertEquals($party->id, $sale->party->id);
    }

    public function test_sale_belongs_to_user()
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $sale->user);
        $this->assertEquals($user->id, $sale->user->id);
    }

    public function test_sale_has_many_details()
    {
        $sale = Sale::factory()->create();
        SaleDetails::factory(3)->create(['sale_id' => $sale->id]);

        $this->assertCount(3, $sale->details);
    }

    public function test_sale_has_many_sale_returns()
    {
        $sale = Sale::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $sale->saleReturns());
    }

    public function test_sale_is_paid_flag_works()
    {
        $paidSale = Sale::factory()->create(['isPaid' => true, 'dueAmount' => 0]);
        $unpaidSale = Sale::factory()->create(['isPaid' => false, 'dueAmount' => 100]);

        $this->assertTrue($paidSale->isPaid);
        $this->assertFalse($unpaidSale->isPaid);
    }
}