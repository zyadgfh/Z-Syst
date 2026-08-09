<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Party;
use App\Models\Plan;
use App\Models\PlanSubscribe;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_sale_workflow()
    {
        // Create business
        $business = Business::factory()->create();

        // Create admin user
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'admin',
        ]);
        $admin->givePermissionTo('sales-create');
        $admin->givePermissionTo('products-create');

        // Create product
        $product = Product::factory()->create([
            'business_id' => $business->id,
            'stock' => 100,
            'price' => 10.00,
        ]);

        // Create customer
        $customer = Party::factory()->create([
            'business_id' => $business->id,
            'type' => 'customer',
        ]);

        // Create sale
        $saleData = [
            'business_id' => $business->id,
            'party_id' => $customer->id,
            'saleDate' => now()->toDateTimeString(),
            'totalAmount' => 100.00,
            'paidAmount' => 100.00,
            'dueAmount' => 0,
            'paymentType' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'price' => 10.00,
                    'total' => 100.00,
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.sales.store'), $saleData);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales', [
            'business_id' => $business->id,
            'party_id' => $customer->id,
            'totalAmount' => 100.00,
        ]);

        // Verify stock was deducted
        $product->refresh();
        $this->assertEquals(90, $product->stock);
    }

    public function test_complete_purchase_workflow()
    {
        // Create business
        $business = Business::factory()->create();

        // Create admin user
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'admin',
        ]);
        $admin->givePermissionTo('purchases-create');
        $admin->givePermissionTo('products-create');

        // Create product
        $product = Product::factory()->create([
            'business_id' => $business->id,
            'stock' => 0,
            'price' => 5.00,
            'purchase_with_tax' => 5.00,
        ]);

        // Create supplier
        $supplier = Party::factory()->create([
            'business_id' => $business->id,
            'type' => 'supplier',
        ]);

        // Create purchase
        $purchaseData = [
            'business_id' => $business->id,
            'party_id' => $supplier->id,
            'purchaseDate' => now()->toDateTimeString(),
            'totalAmount' => 50.00,
            'paidAmount' => 50.00,
            'dueAmount' => 0,
            'paymentType' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'price' => 5.00,
                    'total' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.purchases.store'), $purchaseData);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchases', [
            'business_id' => $business->id,
            'party_id' => $supplier->id,
            'totalAmount' => 50.00,
        ]);

        // Verify stock was added
        $product->refresh();
        $this->assertEquals(10, $product->stock);
    }

    public function test_user_registration_and_login()
    {
        // Create business
        $business = Business::factory()->create();

        // Register new user
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_id' => $business->id,
        ];

        $response = $this->post('/register', $userData);
        $response->assertRedirect();

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'business_id' => $business->id,
        ]);

        // Login
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_subscription_upgrade_workflow()
    {
        // Create business
        $business = Business::factory()->create();

        // Create Super Admin
        $superAdmin = User::factory()->create([
            'role' => 'superadmin',
        ]);

        // Create plans
        $basicPlan = Plan::factory()->create([
            'subscriptionName' => 'Basic',
            'subscriptionPrice' => 29.99,
            'duration' => 30,
        ]);

        $premiumPlan = Plan::factory()->create([
            'subscriptionName' => 'Premium',
            'subscriptionPrice' => 99.99,
            'duration' => 30,
        ]);

        // Create subscription
        $subscription = PlanSubscribe::factory()->create([
            'business_id' => $business->id,
            'plan_id' => $basicPlan->id,
            'price' => 29.99,
            'duration' => 30,
        ]);

        // Upgrade subscription
        $upgradeData = [
            'business_id' => $business->id,
            'plan_id' => $premiumPlan->id,
            'price' => 99.99,
        ];

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.business.upgrade-plan', $business->id), $upgradeData);

        $response->assertRedirect();
        $this->assertDatabaseHas('plan_subscribes', [
            'business_id' => $business->id,
            'plan_id' => $premiumPlan->id,
            'price' => 99.99,
        ]);
    }

    public function test_receipt_generation_workflow()
    {
        // Create business
        $business = Business::factory()->create();

        // Create admin user
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'admin',
        ]);
        $admin->givePermissionTo('sales-create');
        $admin->givePermissionTo('receipts-create');

        // Create product and sale
        $product = Product::factory()->create([
            'business_id' => $business->id,
            'stock' => 100,
            'price' => 10.00,
        ]);

        $customer = Party::factory()->create([
            'business_id' => $business->id,
            'type' => 'customer',
        ]);

        $sale = Sale::factory()->create([
            'business_id' => $business->id,
            'party_id' => $customer->id,
            'totalAmount' => 100.00,
        ]);

        // Generate receipt
        $receiptData = [
            'sale_id' => $sale->id,
            'format' => 'pdf',
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.receipts.generate-sale'), $receiptData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('receipts', [
            'business_id' => $business->id,
            'sale_id' => $sale->id,
            'type' => 'sale',
        ]);
    }

    public function test_loyalty_program_workflow()
    {
        // Create business
        $business = Business::factory()->create();

        // Create admin user
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'admin',
        ]);
        $admin->givePermissionTo('loyalty-create');

        // Create loyalty program
        $programData = [
            'business_id' => $business->id,
            'name' => 'Test Loyalty Program',
            'points_per_currency' => 10,
            'min_points_for_reward' => 100,
            'is_active' => true,
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.loyalty.store'), $programData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('loyalty_programs', [
            'business_id' => $business->id,
            'name' => 'Test Loyalty Program',
        ]);

        // Add loyalty transaction
        $customer = Party::factory()->create([
            'business_id' => $business->id,
            'type' => 'customer',
        ]);

        $transactionData = [
            'business_id' => $business->id,
            'party_id' => $customer->id,
            'program_id' => $response->json('data')['id'],
            'points' => 50,
            'type' => 'earned',
        ];

        $this->actingAs($admin)
            ->post(route('admin.loyalty.create-interaction'), $transactionData);

        $this->assertDatabaseHas('loyalty_transactions', [
            'business_id' => $business->id,
            'party_id' => $customer->id,
            'points' => 50,
        ]);
    }

    public function test_warehouse_transfer_workflow()
    {
        // Create business
        $business = Business::factory()->create();

        // Create admin user
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'admin',
        ]);
        $admin->givePermissionTo('warehouses-create');
        $admin->givePermissionTo('stock-transfers-create');

        // Create warehouses
        $warehouse1 = Warehouse::factory()->create([
            'business_id' => $business->id,
            'name' => 'Warehouse 1',
        ]);

        $warehouse2 = Warehouse::factory()->create([
            'business_id' => $business->id,
            'name' => 'Warehouse 2',
        ]);

        // Create product and stock
        $product = Product::factory()->create([
            'business_id' => $business->id,
            'stock' => 50,
        ]);

        $stock1 = WarehouseStock::factory()->create([
            'warehouse_id' => $warehouse1->id,
            'product_id' => $product->id,
            'quantity' => 30,
        ]);

        // Create transfer
        $transferData = [
            'business_id' => $business->id,
            'from_warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'status' => 'pending',
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.stock-transfers.store'), $transferData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('stock_transfers', [
            'business_id' => $business->id,
            'from_warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
        ]);
    }

    public function test_audit_logging_workflow()
    {
        // Create business
        $business = Business::factory()->create();

        // Create user
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'admin',
        ]);

        // Create product
        $product = Product::factory()->create([
            'business_id' => $business->id,
            'name' => 'Test Product',
        ]);

        // Update product (should trigger audit log)
        $product->update(['name' => 'Updated Product']);

        // Verify audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'user_id' => $user->id,
            'action' => 'updated',
            'model_type' => get_class($product),
            'model_id' => $product->id,
        ]);
    }

    public function test_permission_enforcement()
    {
        // Create business
        $business = Business::factory()->create();

        // Create user without permission
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'staff',
        ]);

        // Try to create product without permission
        $productData = [
            'business_id' => $business->id,
            'name' => 'Test Product',
            'price' => 10.00,
        ];

        $response = $this->actingAs($user)
            ->post(route('admin.products.store'), $productData);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('products', [
            'name' => 'Test Product',
        ]);
    }

    public function test_tenant_isolation()
    {
        // Create two businesses
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();

        // Create users for each business
        $user1 = User::factory()->create([
            'business_id' => $business1->id,
            'role' => 'admin',
        ]);

        $user2 = User::factory()->create([
            'business_id' => $business2->id,
            'role' => 'admin',
        ]);

        // Create product for business 1
        $product1 = Product::factory()->create([
            'business_id' => $business1->id,
            'name' => 'Business 1 Product',
        ]);

        // User 2 should not see business 1's product
        $response = $this->actingAs($user2)
            ->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Business 1 Product');
    }

    public function test_cache_invalidation_on_data_change()
    {
        // Create business
        $business = Business::factory()->create();

        // Create user
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'admin',
        ]);

        // Get statistics (should cache)
        $this->actingAs($user)
            ->get(route('dashboard-reports.overall'));

        // Update data
        Product::factory()->create([
            'business_id' => $business->id,
            'name' => 'New Product',
        ]);

        // Get statistics again (should reflect new data)
        $response = $this->actingAs($user)
            ->get(route('dashboard-reports.overall'));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('data');
    }
}
