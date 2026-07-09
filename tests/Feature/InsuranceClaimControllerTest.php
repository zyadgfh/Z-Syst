<?php

namespace Tests\Feature;

use App\Models\InsuranceClaim;
use App\Models\User;
use App\Models\Patient;
use App\Models\Branch;
use App\Models\InsuranceCompany;
use App\Models\InsurancePlan;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceClaimControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Patient $patient;
    protected InsuranceCompany $insuranceCompany;
    protected InsurancePlan $insurancePlan;
    protected Sale $sale;

    protected function setUp(): void
    {
        parent::setUp();

        $company = \App\Models\Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->patient = Patient::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->insuranceCompany = InsuranceCompany::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->insurancePlan = InsurancePlan::factory()->create([
            'company_id' => $company->id,
            'insurance_company_id' => $this->insuranceCompany->id,
        ]);

        $this->sale = Sale::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_list_insurance_claims()
    {
        InsuranceClaim::factory()->count(3)->create([
            'company_id' => $this->user->company_id,
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'insurance_plan_id' => $this->insurancePlan->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $this->sale->id,
            'submitted_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/admin/insurance-claims');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');
    }

    public function test_can_create_insurance_claim()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/admin/insurance-claims', [
                'sale_id' => $this->sale->id,
                'insurance_company_id' => $this->insuranceCompany->id,
                'insurance_plan_id' => $this->insurancePlan->id,
                'patient_id' => $this->patient->id,
                'branch_id' => $this->branch->id,
                'amount_claimed' => 100.00,
                'co_pay_amount' => 20.00,
                'notes' => 'Test insurance claim',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount_claimed', '100.00')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('insurance_claims', [
            'amount_claimed' => 100.00,
            'company_id' => $this->user->company_id,
        ]);
    }

    public function test_cannot_create_insurance_claim_without_authentication()
    {
        $response = $this->postJson('/api/v1/admin/insurance-claims', [
            'sale_id' => $this->sale->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'patient_id' => $this->patient->id,
            'branch_id' => $this->branch->id,
            'amount_claimed' => 100.00,
        ]);

        $response->assertStatus(401);
    }

    public function test_can_show_insurance_claim()
    {
        $claim = InsuranceClaim::factory()->create([
            'company_id' => $this->user->company_id,
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'insurance_plan_id' => $this->insurancePlan->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $this->sale->id,
            'submitted_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/admin/insurance-claims/{$claim->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $claim->id);
    }

    public function test_cannot_access_insurance_claim_from_different_company()
    {
        $otherCompany = \App\Models\Company::factory()->create();
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $claim = InsuranceClaim::factory()->create([
            'company_id' => $this->user->company_id,
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'insurance_plan_id' => $this->insurancePlan->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $this->sale->id,
            'submitted_by' => $this->user->id,
        ]);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/admin/insurance-claims/{$claim->id}");

        $response->assertStatus(404);
    }

    public function test_can_submit_insurance_claim()
    {
        $claim = InsuranceClaim::factory()->create([
            'company_id' => $this->user->company_id,
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'insurance_plan_id' => $this->insurancePlan->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $this->sale->id,
            'submitted_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/admin/insurance-claims/{$claim->id}/submit");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('insurance_claims', [
            'id' => $claim->id,
            'status' => 'submitted',
        ]);
    }

    public function test_cannot_submit_processed_claim()
    {
        $claim = InsuranceClaim::factory()->create([
            'company_id' => $this->user->company_id,
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'insurance_plan_id' => $this->insurancePlan->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $this->sale->id,
            'submitted_by' => $this->user->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/admin/insurance-claims/{$claim->id}/submit");

        $response->assertStatus(422);
    }

    public function test_can_approve_insurance_claim()
    {
        $claim = InsuranceClaim::factory()->create([
            'company_id' => $this->user->company_id,
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'insurance_plan_id' => $this->insurancePlan->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $this->sale->id,
            'submitted_by' => $this->user->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/admin/insurance-claims/{$claim->id}/approve", [
                'amount_approved' => 80.00,
                'settlement_amount' => 80.00,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.amount_approved', '80.00');

        $this->assertDatabaseHas('insurance_claims', [
            'id' => $claim->id,
            'status' => 'approved',
        ]);
    }

    public function test_can_reject_insurance_claim()
    {
        $claim = InsuranceClaim::factory()->create([
            'company_id' => $this->user->company_id,
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'insurance_plan_id' => $this->insurancePlan->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $this->sale->id,
            'submitted_by' => $this->user->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/admin/insurance-claims/{$claim->id}/reject", [
                'rejection_reason' => 'Coverage limit exceeded',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Coverage limit exceeded');

        $this->assertDatabaseHas('insurance_claims', [
            'id' => $claim->id,
            'status' => 'rejected',
        ]);
    }

    public function test_can_update_pending_claim()
    {
        $claim = InsuranceClaim::factory()->create([
            'company_id' => $this->user->company_id,
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'insurance_plan_id' => $this->insurancePlan->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $this->sale->id,
            'submitted_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/admin/insurance-claims/{$claim->id}", [
                'amount_claimed' => 150.00,
                'notes' => 'Updated claim details',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.amount_claimed', '150.00');
    }

    public function test_cannot_update_processed_claim()
    {
        $claim = InsuranceClaim::factory()->create([
            'company_id' => $this->user->company_id,
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'insurance_plan_id' => $this->insurancePlan->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $this->sale->id,
            'submitted_by' => $this->user->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/admin/insurance-claims/{$claim->id}", [
                'amount_claimed' => 150.00,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_delete_pending_claim()
    {
        $claim = InsuranceClaim::factory()->create([
            'company_id' => $this->user->company_id,
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'insurance_plan_id' => $this->insurancePlan->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $this->sale->id,
            'submitted_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/admin/insurance-claims/{$claim->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('insurance_claims', [
            'id' => $claim->id,
        ]);
    }

    public function test_cannot_delete_processed_claim()
    {
        $claim = InsuranceClaim::factory()->create([
            'company_id' => $this->user->company_id,
            'patient_id' => $this->patient->id,
            'insurance_company_id' => $this->insuranceCompany->id,
            'insurance_plan_id' => $this->insurancePlan->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $this->sale->id,
            'submitted_by' => $this->user->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/admin/insurance-claims/{$claim->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('insurance_claims', [
            'id' => $claim->id,
        ]);
    }
}