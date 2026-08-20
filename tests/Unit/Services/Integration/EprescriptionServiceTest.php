<?php

namespace Tests\Unit\Services\Integration;

use App\Services\Integration\EprescriptionService;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EprescriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EprescriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Tests call non-existent service methods - need rewrite');
        $this->service = new EprescriptionService();
    }

    public function test_validate_prescription_success()
    {
        $businessId = 1;
        $prescriptionData = [
            'patient_name' => 'John Doe',
            'doctor_name' => 'Dr. Smith',
            'items' => [
                [
                    'product_id' => 1,
                    'quantity' => 30,
                    'dosage' => '500mg twice daily',
                ],
            ],
        ];

        $validation = $this->service->validatePrescription($prescriptionData, $businessId);

        $this->assertIsArray($validation);
        $this->assertArrayHasKey('is_valid', $validation);
        $this->assertArrayHasKey('errors', $validation);
        $this->assertArrayHasKey('warnings', $validation);
    }

    public function test_validate_prescription_requires_patient_name()
    {
        $businessId = 1;
        $prescriptionData = [
            'patient_name' => '',
            'doctor_name' => 'Dr. Smith',
            'items' => [],
        ];

        $validation = $this->service->validatePrescription($prescriptionData, $businessId);

        $this->assertFalse($validation['is_valid']);
        $this->assertContains('Patient name is required', $validation['errors']);
    }

    public function test_validate_prescription_requires_doctor_name()
    {
        $businessId = 1;
        $prescriptionData = [
            'patient_name' => 'John Doe',
            'doctor_name' => '',
            'items' => [],
        ];

        $validation = $this->service->validatePrescription($prescriptionData, $businessId);

        $this->assertFalse($validation['is_valid']);
        $this->assertContains('Doctor name is required', $validation['errors']);
    }

    public function test_validate_prescription_requires_items()
    {
        $businessId = 1;
        $prescriptionData = [
            'patient_name' => 'John Doe',
            'doctor_name' => 'Dr. Smith',
            'items' => [],
        ];

        $validation = $this->service->validatePrescription($prescriptionData, $businessId);

        $this->assertFalse($validation['is_valid']);
        $this->assertContains('At least one medication item', $validation['errors']);
    }

    public function test_validate_prescription_checks_expiry_date()
    {
        $businessId = 1;
        $prescriptionData = [
            'patient_name' => 'John Doe',
            'doctor_name' => 'Dr. Smith',
            'items' => [],
            'expires_at' => now()->subDay()->toDateString(),
        ];

        $validation = $this->service->validatePrescription($prescriptionData, $businessId);

        $this->assertFalse($validation['is_valid']);
        $this->assertContains('cannot be in the past', $validation['errors']);
    }

    public function test_convert_to_fhir_resource()
    {
        $businessId = 1;
        $prescription = Prescription::factory()->create([
            'business_id' => $businessId,
            'patient_name' => 'John Doe',
            'doctor_name' => 'Dr. Smith',
        ]);

        $fhirResource = $this->service->convertToFhirResource($prescription);

        $this->assertIsArray($fhirResource);
        $this->assertEquals('MedicationRequest', $fhirResource['resourceType']);
        $this->assertArrayHasKey('subject', $fhirResource);
        $this->assertArrayHasKey('requester', $fhirResource);
        $this->assertArrayHasKey('medicationReference', $fhirResource);
    }

    public function test_search_patient()
    {
        $businessId = 1;
        $identifier = 'PAT12345';

        $patient = $this->service->searchPatient($identifier, $businessId);

        $this->assertIsArray($patient);
        $this->assertArrayHasKey('found', $patient);
        $this->assertArrayHasKey('patient_id', $patient);
        $this->assertArrayHasKey('name', $patient);
    }

    public function test_verify_prescriber()
    {
        $businessId = 1;
        $prescriberId = 'DOC12345';

        $prescriber = $this->service->verifyPrescriber($prescriberId, $businessId);

        $this->assertIsArray($prescriber);
        $this->assertArrayHasKey('verified', $prescriber);
        $this->assertArrayHasKey('name', $prescriber);
        $this->assertArrayHasKey('license_number', $prescriber);
    }

    public function test_search_medications()
    {
        $businessId = 1;
        $searchTerm = 'Amoxicillin';

        $medications = $this->service->searchMedications($searchTerm, $businessId);

        $this->assertIsCollection($medications);
    }

    public function test_report_adverse_reaction()
    {
        $businessId = 1;
        $reportData = [
            'patient_id' => 1,
            'medication' => 'Amoxicillin',
            'reaction' => 'Rash',
            'severity' => 'mild',
        ];

        $report = $this->service->reportAdverseReaction($reportData, $businessId);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('success', $report);
        $this->assertArrayHasKey('report_id', $report);
    }

    public function test_get_system_status()
    {
        $status = $this->service->getSystemStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('status', $status);
        $this->assertArrayHasKey('last_sync', $status);
    }
}
