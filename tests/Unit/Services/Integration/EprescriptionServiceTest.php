<?php

namespace Tests\Unit\Services\Integration;

use App\Models\Business;
use App\Models\User;
use App\Services\Integration\EprescriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EprescriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EprescriptionService $service;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        User::factory()->create(['business_id' => $this->business->id]);

        $this->service = new EprescriptionService();
    }

    public function test_validate_prescription_requires_patient_name(): void
    {
        $result = $this->service->validatePrescription([
            'doctor_name' => 'Dr. Smith',
            'items' => [['medication' => 'Aspirin', 'quantity' => 1]],
        ], $this->business->id);

        $this->assertArrayHasKey('is_valid', $result);
    }

    public function test_validate_prescription_requires_doctor_name(): void
    {
        $result = $this->service->validatePrescription([
            'patient_name' => 'John Doe',
            'items' => [['medication' => 'Aspirin', 'quantity' => 1]],
        ], $this->business->id);

        $this->assertArrayHasKey('is_valid', $result);
    }

    public function test_validate_prescription_requires_items(): void
    {
        $result = $this->service->validatePrescription([
            'patient_name' => 'John Doe',
            'doctor_name' => 'Dr. Smith',
            'items' => [],
        ], $this->business->id);

        $this->assertArrayHasKey('is_valid', $result);
    }

    public function test_validate_prescription_success(): void
    {
        $result = $this->service->validatePrescription([
            'patient_name' => 'John Doe',
            'doctor_name' => 'Dr. Smith',
            'items' => [['medication' => 'Aspirin', 'quantity' => 1]],
        ], $this->business->id);

        $this->assertArrayHasKey('is_valid', $result);
    }

    public function test_search_medications_returns_collection(): void
    {
        $result = $this->service->searchMedications('aspirin', $this->business->id);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_get_system_status_returns_array(): void
    {
        $result = $this->service->getSystemStatus();

        $this->assertIsArray($result);
    }
}
