<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Models\Party;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            'invoice_number' => $sale->invoice_number,
        ]);
    }

    public function test_sale_has_fillable_attributes()
    {
        $fillable = (new Sale)->getFillable();

        $this->assertContains('total_amount', $fillable);
        $this->assertContains('paid_amount', $fillable);
        $this->assertContains('due_amount', $fillable);
        $this->assertContains('is_paid', $fillable);
        $this->assertContains('party_id', $fillable);
        $this->assertContains('business_id', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('invoice_number', $fillable);
    }

    public function test_sale_has_casts()
    {
        $casts = (new Sale)->getCasts();

        $this->assertArrayHasKey('is_paid', $casts);
        $this->assertEquals('boolean', $casts['is_paid']);
        $this->assertArrayHasKey('total_amount', $casts);
        $this->assertEquals('double', $casts['total_amount']);
        $this->assertArrayHasKey('due_amount', $casts);
        $this->assertEquals('double', $casts['due_amount']);
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

        $this->assertInstanceOf(HasMany::class, $sale->saleReturns());
    }

    public function test_sale_is_paid_flag_works()
    {
        $paidSale = Sale::factory()->create(['is_paid' => true, 'due_amount' => 0]);
        $unpaidSale = Sale::factory()->create(['is_paid' => false, 'due_amount' => 100]);

        $this->assertTrue($paidSale->is_paid);
        $this->assertFalse($unpaidSale->is_paid);
    }
}
