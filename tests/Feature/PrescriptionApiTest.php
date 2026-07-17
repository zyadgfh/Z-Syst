<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Prescription;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_can_list_prescriptions(): void
    {
        Prescription::factory()->count(3)->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/prescriptions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'prescription_number', 'status']
                ]
            ]);
    }

    public function test_can_create_prescription(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->user->company_id]);
        $doctor = Doctor::factory()->create(['company_id' => $this->user->company_id]);
        $branch = Branch::factory()->create(['company_id' => $this->user->company_id]);
        $product = Product::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/prescriptions', [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'branch_id' => $branch->id,
                'prescribed_date' => now()->format('Y-m-d'),
                'notes' => 'Test prescription',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'dosage' => '10mg',
                        'frequency' => 'Once daily',
                        'duration' => '7 days',
                        'quantity' => 7,
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('patient_id', $patient->id)
            ->assertJsonPath('status', 'pending');
    }

    public function test_can_show_prescription(): void
    {
        $prescription = Prescription::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/prescriptions/{$prescription->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $prescription->id);
    }

    public function test_cannot_access_other_company_prescription(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        
        $patient = Patient::factory()->create(['company_id' => $otherCompany->id]);
        $doctor = Doctor::factory()->create(['company_id' => $otherCompany->id]);
        $branch = Branch::factory()->create(['company_id' => $otherCompany->id]);
        $product = Product::factory()->create(['company_id' => $otherCompany->id]);
        
        $prescription = Prescription::create([
            'company_id' => $otherCompany->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'branch_id' => $branch->id,
            'prescribed_date' => now(),
            'status' => 'pending',
            'prescription_number' => 'RX-TEST',
            'created_by' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/prescriptions/{$prescription->id}");

        $response->assertStatus(403);
    }
}