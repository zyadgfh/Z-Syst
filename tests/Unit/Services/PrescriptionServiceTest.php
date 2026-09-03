<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\User;
use App\Services\PrescriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PrescriptionService $service;
    protected Business $business;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);

        $this->service = new PrescriptionService();
    }

    public function test_create_prescription(): void
    {
        $doctor = Doctor::factory()->create(['business_id' => $this->business->id]);

        $prescription = $this->service->createPrescription([
            'patient_name' => 'John Doe',
            'doctor_id' => $doctor->id,
            'prescription_date' => now()->toDateString(),
            'image' => 'test-image.jpg',
        ], $this->business->id);

        $this->assertInstanceOf(Prescription::class, $prescription);
        $this->assertEquals('John Doe', $prescription->patient_name);
    }

    public function test_update_prescription(): void
    {
        $doctor = Doctor::factory()->create(['business_id' => $this->business->id]);
        $prescription = $this->service->createPrescription([
            'patient_name' => 'John Doe',
            'doctor_id' => $doctor->id,
            'prescription_date' => now()->toDateString(),
            'image' => 'test-image.jpg',
        ], $this->business->id);

        $updated = $this->service->updatePrescription($prescription, [
            'patient_name' => 'Jane Doe',
        ]);

        $this->assertEquals('Jane Doe', $updated->patient_name);
    }

    public function test_delete_prescription(): void
    {
        $doctor = Doctor::factory()->create(['business_id' => $this->business->id]);
        $prescription = $this->service->createPrescription([
            'patient_name' => 'John Doe',
            'doctor_id' => $doctor->id,
            'prescription_date' => now()->toDateString(),
            'image' => 'test-image.jpg',
        ], $this->business->id);

        $result = $this->service->deletePrescription($prescription);

        $this->assertTrue($result);
    }

    public function test_get_prescription_statistics(): void
    {
        $stats = $this->service->getPrescriptionStatistics($this->business->id);

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('used', $stats);
    }
}
