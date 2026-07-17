<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Company;
use App\Models\Doctor;
use App\Models\Drug;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeDrugLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_lookup_drug_by_barcode_for_current_tenant(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user, 'sanctum');

        Drug::create([
            'company_id' => $company->id,
            'name' => 'Amoxicillin 500mg',
            'barcode' => 'AMX500',
        ]);

        $response = $this->getJson('/api/v1/drugs/barcode/AMX500');

        $response->assertStatus(200)
            ->assertJsonPath('data.barcode', 'AMX500')
            ->assertJsonPath('data.name', 'Amoxicillin 500mg');
    }

    public function test_returns_not_found_for_unknown_barcode(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/drugs/barcode/UNKNOWN');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Drug not found');
    }

    public function test_can_return_pos_summary_with_forecast_data(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user, 'sanctum');

        $patient = Patient::factory()->create(['company_id' => $company->id]);
        $doctor = Doctor::factory()->create(['company_id' => $company->id]);
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $businessCategory = BusinessCategory::create(['name' => 'Test Category', 'status' => 'active']);
        $business = Business::create([
            'business_category_id' => $businessCategory->id,
            'companyName' => 'Demo Business',
        ]);
        $product = Product::create([
            'company_id' => $company->id,
            'business_id' => $business->id,
            'productName' => 'Vitamin C',
            'purchase_without_tax' => 5,
            'purchase_with_tax' => 6,
            'profit_percent' => 20,
            'sales_price' => 10,
            'alert_qty' => 5,
            'barcode' => 'VITC100',
        ]);

        $prescription = Prescription::create([
            'company_id' => $company->id,
            'prescription_number' => 'RX-TEST-1',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'branch_id' => $branch->id,
            'status' => 'pending',
            'prescribed_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $prescription->items()->create([
            'product_id' => $product->id,
            'dosage' => '1 tab',
            'frequency' => 'daily',
            'duration' => '7 days',
            'quantity' => 10,
            'dispensed_quantity' => 0,
            'substitution_allowed' => true,
        ]);

        ActivityLog::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'action' => 'stock_movement.out',
            'description' => 'Dispensed 4 units',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'performed_at' => now(),
            'properties' => [
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'movement_type' => 'out',
                'quantity' => 4,
                'reference_type' => 'prescription_dispense',
                'reference_id' => $prescription->id,
            ],
        ]);

        $response = $this->getJson('/api/v1/pharmacy/pos-summary');

        $response->assertStatus(200)
            ->assertJsonPath('pending_prescriptions_count', 1)
            ->assertJsonPath('forecast.0.product_id', $product->id);
    }

    public function test_can_complete_pos_checkout_and_reduce_stock(): void
    {
        $company = Company::factory()->create(['slug' => 'demo']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user, 'sanctum');

        $businessCategory = BusinessCategory::create(['name' => 'Test Category', 'status' => 'active']);
        $business = Business::create([
            'business_category_id' => $businessCategory->id,
            'companyName' => 'Demo Business',
        ]);
        $product = Product::create([
            'company_id' => $company->id,
            'business_id' => $business->id,
            'productName' => 'Pain Relief',
            'purchase_without_tax' => 3,
            'purchase_with_tax' => 3.6,
            'profit_percent' => 25,
            'sales_price' => 5,
            'alert_qty' => 5,
            'barcode' => 'PIL100',
        ]);
        $stock = Stock::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'business_id' => $business->id,
            'productStock' => 20,
        ]);

        $response = $this->postJson('/api/v1/pharmacy/checkout', [
            'items' => [
                ['barcode' => 'PIL100', 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('items.0.barcode', 'PIL100')
            ->assertJsonPath('items.0.quantity', 3);

        $stock->fresh();
        $this->assertSame(17, $stock->productStock);
    }
}
