<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\Sale;
use App\Models\InsuranceClaim;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceClaimApiTest extends TestCase
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

    public function test_can_list_insurance_claims(): void
    {
        InsuranceClaim::factory()->count(3)->create([
            'insurance_company_id' => InsuranceCompany::factory()->create(['company_id' => $this->user->company_id])->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/insurance-claims');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'claim_number', 'status']
                ]
            ]);
    }

    public function test_can_create_insurance_claim(): void
    {
        $insuranceCompany = InsuranceCompany::factory()->create(['company_id' => $this->user->company_id]);
        $patient = Patient::factory()->create(['company_id' => $this->user->company_id]);
        $sale = Sale::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/insurance-claims', [
                'insurance_company_id' => $insuranceCompany->id,
                'patient_id' => $patient->id,
                'sale_id' => $sale->id,
                'amount_claimed' => 100.50,
                'notes' => 'Test claim',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('insurance_company_id', $insuranceCompany->id)
            ->assertJsonPath('status', 'pending');
    }

    public function test_can_approve_claim(): void
    {
        $claim = InsuranceClaim::factory()->create([
            'insurance_company_id' => InsuranceCompany::factory()->create(['company_id' => $this->user->company_id])->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/insurance-claims/{$claim->id}", [
                'status' => 'approved',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'approved');
    }

    public function test_cannot_delete_approved_claim(): void
    {
        $claim = InsuranceClaim::factory()->create([
            'insurance_company_id' => InsuranceCompany::factory()->create(['company_id' => $this->user->company_id])->id,
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/insurance-claims/{$claim->id}");

        $response->assertStatus(422);
    }
}