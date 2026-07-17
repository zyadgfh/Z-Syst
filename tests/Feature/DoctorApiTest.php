<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $company->id]);
    }

    public function test_can_list_doctors(): void
    {
        Doctor::factory()->count(3)->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/doctors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'specialization']
                ]
            ]);
    }

    public function test_can_create_doctor(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/doctors', [
                'name' => 'Dr. John Smith',
                'specialization' => 'Cardiology',
                'license_number' => 'MED123456',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Dr. John Smith');
    }

    public function test_cannot_create_doctor_with_duplicate_license(): void
    {
        Doctor::factory()->create([
            'company_id' => $this->user->company_id,
            'license_number' => 'MED123456'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/doctors', [
                'name' => 'Dr. Jane Doe',
                'specialization' => 'Cardiology',
                'license_number' => 'MED123456',
                'phone' => '0987654321',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_update_doctor(): void
    {
        $doctor = Doctor::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/doctors/{$doctor->id}", [
                'clinic_name' => 'Updated Clinic',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('clinic_name', 'Updated Clinic');
    }

    public function test_can_delete_doctor(): void
    {
        $doctor = Doctor::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/doctors/{$doctor->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('doctors', ['id' => $doctor->id]);
    }
}