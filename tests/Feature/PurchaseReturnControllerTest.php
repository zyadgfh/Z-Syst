<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Party;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\PurchaseReturn;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseReturnControllerTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        app()->instance('tenant.company_id', $this->company->id);
    }

    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'business_id' => $this->company->id,
        ]);
        Sanctum::actingAs($user, [], 'sanctum');

        return $user;
    }

    public function test_can_create_purchase_return_and_decrease_stock(): void
    {
        $user = $this->createAuthenticatedUser();
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'supplier',
        ]);
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create purchase with detail
        $purchase = Purchase::factory()->create([
            'company_id' => $this->company->id,
            'party_id'     => $party->id,
            'user_id'      => $user->id,
        ]);

        $purchaseDetail = PurchaseDetails::factory()->create([
            'purchase_id' => $purchase->id,
            'product_id'  => $product->id,
            'quantities'  => 20,
        ]);

        // Create initial stock record for the product
        $stock = Stock::factory()->create([
            'company_id'   => $this->company->id,
            'product_id'   => $product->id,
            'productStock' => 50,
        ]);

        $returnQty    = 5;
        $returnAmount = 50.00;

        $response = $this->postJson('/api/v1/purchases-return', [
            'purchase_id'        => $purchase->id,
            'return_date'        => now()->format('Y-m-d'),
            'purchase_detail_id' => [$purchaseDetail->id],
            'return_amount'      => [$returnAmount],
            'return_qty'         => [$returnQty],
            'dueAmount'          => 0,
            'paidAmount'         => 50,
            'totalAmount'        => 100,
            'discountAmount'     => 0,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', __('Data saved successfully.'))
            ->assertJsonStructure([
                'data' => ['id', 'purchase_id', 'invoice_no', 'return_date'],
            ]);

        // 1) Purchase return record exists
        $this->assertDatabaseHas('purchase_returns', [
            'purchase_id' => $purchase->id,
        ]);

        // 2) Purchase return detail inserted
        $this->assertDatabaseHas('purchase_return_details', [
            'purchase_detail_id' => $purchaseDetail->id,
            'return_qty'         => $returnQty,
            'return_amount'      => $returnAmount,
        ]);

        // 3) Stock DECREASED via StockAllocationService::allocate()  (50 - 5 = 45)
        $this->assertDatabaseHas('stocks', [
            'id'           => $stock->id,
            'productStock' => 45,
        ]);

        // 4) Purchase detail quantities decreased (20 - 5 = 15)
        $this->assertDatabaseHas('purchase_details', [
            'id'         => $purchaseDetail->id,
            'quantities' => 15,
        ]);
    }

    public function test_validates_required_fields_for_purchase_return(): void
    {
        $this->createAuthenticatedUser();

        $response = $this->postJson('/api/v1/purchases-return', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'purchase_id',
                'return_date',
                'purchase_detail_id',
                'return_amount',
                'return_qty',
            ]);
    }

    public function test_can_list_purchase_returns_with_date_filter(): void
    {
        $user = $this->createAuthenticatedUser();
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'type'       => 'supplier',
        ]);
        $purchase = Purchase::factory()->create([
            'company_id' => $this->company->id,
            'party_id'   => $party->id,
            'user_id'    => $user->id,
        ]);

        PurchaseReturn::factory()->count(3)->create([
            'company_id'  => $this->company->id,
            'purchase_id' => $purchase->id,
            'return_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/purchases-return?start_date='
                . now()->subDays(1)->format('Y-m-d')
                . '&end_date='
                . now()->addDays(1)->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'message'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_show_purchase_return_with_relations(): void
    {
        $user = $this->createAuthenticatedUser();
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'type'       => 'supplier',
        ]);
        $purchase = Purchase::factory()->create([
            'company_id' => $this->company->id,
            'party_id'   => $party->id,
            'user_id'    => $user->id,
        ]);

        $purchaseReturn = PurchaseReturn::factory()->create([
            'company_id'  => $this->company->id,
            'purchase_id' => $purchase->id,
            'return_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/purchases-return/' . $purchaseReturn->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $purchaseReturn->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'purchase_id',
                    'invoice_no',
                    'return_date',
                    'purchase',
                    'details',
                ],
            ]);
    }

    public function test_returns_error_when_stock_insufficient_for_purchase_return(): void
    {
        $user = $this->createAuthenticatedUser();
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'type'       => 'supplier',
        ]);
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $purchase = Purchase::factory()->create([
            'company_id' => $this->company->id,
            'party_id'   => $party->id,
            'user_id'    => $user->id,
        ]);

        $purchaseDetail = PurchaseDetails::factory()->create([
            'purchase_id' => $purchase->id,
            'product_id'  => $product->id,
            'quantities'  => 10,
        ]);

        // Only 2 items in stock — trying to allocate 10 should fail
        Stock::factory()->create([
            'company_id'   => $this->company->id,
            'product_id'   => $product->id,
            'productStock' => 2,
        ]);

        $response = $this->postJson('/api/v1/purchases-return', [
            'purchase_id'        => $purchase->id,
            'return_date'        => now()->format('Y-m-d'),
            'purchase_detail_id' => [$purchaseDetail->id],
            'return_amount'      => [100],
            'return_qty'         => [10], // exceeds available stock
        ]);

        $response->assertStatus(500)
            ->assertJsonStructure(['error']);
    }
}

