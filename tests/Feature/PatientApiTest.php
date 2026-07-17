<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $company->id]);
    }

    public function test_can_list_patients(): void
    {
        Patient::factory()->count(3)->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/patients');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'phone']
                ]
            ]);
    }

    public function test_can_create_patient(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/patients', [
                'name' => 'John Doe',
                'phone' => '1234567890',
                'email' => 'john@example.com',
                'address' => '123 Main St',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'John Doe');
    }

    public function test_cannot_create_patient_with_duplicate_phone(): void
    {
        Patient::factory()->create([
            'company_id' => $this->user->company_id,
            'phone' => '1234567890'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/patients', [
                'name' => 'Jane Doe',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_show_patient(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/patients/{$patient->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $patient->id);
    }

    public function test_can_update_patient(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/patients/{$patient->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Name');
    }

    public function test_can_delete_patient(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/patients/{$patient->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
    }
}