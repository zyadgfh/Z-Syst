<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Party;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleReturnControllerTest extends TestCase
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

    public function test_can_create_sale_return_and_increase_stock(): void
    {
        $user = $this->createAuthenticatedUser();
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'type'       => 'customer',
        ]);
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create sale with detail
        $sale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'party_id'   => $party->id,
            'user_id'    => $user->id,
        ]);

        $saleDetail = SaleDetails::factory()->create([
            'sale_id'    => $sale->id,
            'product_id' => $product->id,
            'quantities' => 20,
        ]);

        // Create initial stock record
        $stock = Stock::factory()->create([
            'company_id'   => $this->company->id,
            'product_id'   => $product->id,
            'productStock' => 30,
        ]);

        $returnQty    = 4;
        $returnAmount = 40.00;

        $response = $this->postJson('/api/v1/sales-return', [
            'sale_id'        => $sale->id,
            'return_date'    => now()->format('Y-m-d'),
            'sale_detail_id' => [$saleDetail->id],
            'return_amount'  => [$returnAmount],
            'return_qty'     => [$returnQty],
            'dueAmount'      => 0,
            'paidAmount'     => 40,
            'totalAmount'    => 80,
            'discountAmount' => 0,
            'lossProfit'     => [10],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', __('Data saved successfully.'))
            ->assertJsonStructure([
                'data' => ['id', 'sale_id', 'invoice_no', 'return_date'],
            ]);

        // 1) Sale return record exists
        $this->assertDatabaseHas('sale_returns', [
            'sale_id' => $sale->id,
        ]);

        // 2) Sale return details inserted
        $this->assertDatabaseHas('sale_return_details', [
            'sale_detail_id' => $saleDetail->id,
            'return_qty'     => $returnQty,
            'return_amount'  => $returnAmount,
        ]);

        // 3) Stock INCREASED via StockAllocationService::release()  (30 + 4 = 34)
        $this->assertDatabaseHas('stocks', [
            'id'           => $stock->id,
            'productStock' => 34,
        ]);

        // 4) Sale detail quantities decreased (20 - 4 = 16)
        $this->assertDatabaseHas('sale_details', [
            'id'         => $saleDetail->id,
            'quantities' => 16,
        ]);
    }

    public function test_validates_required_fields_for_sale_return(): void
    {
        $this->createAuthenticatedUser();

        $response = $this->postJson('/api/v1/sales-return', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'sale_id',
                'return_date',
                'sale_detail_id',
                'return_amount',
                'return_qty',
            ]);
    }

    public function test_can_list_sale_returns_with_date_filter(): void
    {
        $user = $this->createAuthenticatedUser();
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'type'       => 'customer',
        ]);
        $sale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'party_id'   => $party->id,
            'user_id'    => $user->id,
        ]);

        SaleReturn::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'sale_id'    => $sale->id,
            'return_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sales-return?start_date='
                . now()->subDays(1)->format('Y-m-d')
                . '&end_date='
                . now()->addDays(1)->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'message'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_show_sale_return_with_relations(): void
    {
        $user = $this->createAuthenticatedUser();
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'type'       => 'customer',
        ]);
        $sale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'party_id'   => $party->id,
            'user_id'    => $user->id,
        ]);

        $saleReturn = SaleReturn::factory()->create([
            'company_id' => $this->company->id,
            'sale_id'    => $sale->id,
            'return_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/sales-return/' . $saleReturn->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $saleReturn->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'sale_id',
                    'invoice_no',
                    'return_date',
                    'sale',
                    'details',
                ],
            ]);
    }

    public function test_returns_error_when_no_stock_record_exists_for_sale_return(): void
    {
        $user = $this->createAuthenticatedUser();
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'type'       => 'customer',
        ]);
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $sale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'party_id'   => $party->id,
            'user_id'    => $user->id,
        ]);

        $saleDetail = SaleDetails::factory()->create([
            'sale_id'    => $sale->id,
            'product_id' => $product->id,
            'quantities' => 5,
        ]);

        // Do NOT create any Stock record — StockAllocationService::release() should fail

        $response = $this->postJson('/api/v1/sales-return', [
            'sale_id'        => $sale->id,
            'return_date'    => now()->format('Y-m-d'),
            'sale_detail_id' => [$saleDetail->id],
            'return_amount'  => [50],
            'return_qty'     => [2],
        ]);

        $response->assertStatus(500)
            ->assertJsonStructure(['error']);
    }
}

