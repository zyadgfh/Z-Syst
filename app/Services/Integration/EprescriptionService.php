<?php

namespace App\Services\Integration;

use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Services\PrescriptionService;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EprescriptionService
{
    use WithTransactionalOperations;

    /**
     * Validate prescription against e-prescription system.
     *
     * @param array<string, mixed> $prescriptionData
     * @param int $businessId
     * @return array
     */
    public function validatePrescription(array $prescriptionData, int $businessId): array
    {
        $validationResult = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'external_id' => null,
        ];

        try {
            // Simulate HL7 FHIR validation
            // In production, this would call actual e-prescription API
            
            $validationResult = $this->simulateFhirValidation($prescriptionData);
            
            // Log validation attempt
            Log::info('E-prescription validation attempted', [
                'business_id' => $businessId,
                'prescription_data' => $prescriptionData,
                'result' => $validationResult,
            ]);

        } catch (\Exception $e) {
            $validationResult['is_valid'] = false;
            $validationResult['errors'][] = 'Validation service unavailable: ' . $e->getMessage();
        }

        return $validationResult;
    }

    /**
     * Submit prescription to e-prescription system.
     *
     * @param Prescription $prescription
     * @return array
     */
    public function submitPrescription(Prescription $prescription): array
    {
        return $this->executeTransaction(function () use ($prescription) {
            $submissionResult = [
                'success' => false,
                'external_id' => null,
                'status' => 'failed',
                'message' => '',
            ];

            try {
                // Prepare FHIR resource
                $fhirResource = $this->convertToFhirResource($prescription);

                // Submit to e-prescription system
                $response = $this->submitToFhirSystem($fhirResource);

                if ($response['success']) {
                    $prescription->update([
                        'external_prescription_id' => $response['external_id'],
                        'eprescription_status' => 'submitted',
                        'submitted_at' => now(),
                    ]);

                    $submissionResult = [
                        'success' => true,
                        'external_id' => $response['external_id'],
                        'status' => 'submitted',
                        'message' => 'Prescription submitted successfully',
                    ];
                } else {
                    $submissionResult['message'] = $response['error'];
                }

            } catch (\Exception $e) {
                $submissionResult['message'] = 'Submission failed: ' . $e->getMessage();
            }

            return $submissionResult;
        });
    }

    /**
     * Sync prescription status from e-prescription system.
     *
     * @param Prescription $prescription
     * @return array
     */
    public function syncPrescriptionStatus(Prescription $prescription): array
    {
        if (!$prescription->external_prescription_id) {
            return [
                'success' => false,
                'message' => 'No external prescription ID found',
            ];
        }

        try {
            $status = $this->getPrescriptionStatusFromFhir($prescription->external_prescription_id);

            $prescription->update([
                'eprescription_status' => $status['status'],
                'eprescription_synced_at' => now(),
            ]);

            return [
                'success' => true,
                'status' => $status['status'],
                'message' => 'Status synced successfully',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Convert prescription to FHIR resource.
     *
     * @param Prescription $prescription
     * @return array
     */
    protected function convertToFhirResource(Prescription $prescription): array
    {
        $resource = [
            'resourceType' => 'MedicationRequest',
            'id' => $prescription->id,
            'status' => 'active',
            'intent' => 'order',
            'subject' => [
                'reference' => 'Patient/' . $prescription->party_id,
                'display' => $prescription->patient_name,
            ],
            'authoredOn' => $prescription->created_at->toIso8601String(),
            'requester' => [
                'reference' => 'Practitioner/' . $prescription->doctor_name,
                'display' => $prescription->doctor_name,
            ],
            'dispenseRequest' => [
                'validityPeriod' => [
                    'start' => $prescription->created_at->toIso8601String(),
                    'end' => $prescription->expires_at ? $prescription->expires_at->toIso8601String() : null,
                ],
            ],
            'medicationReference' => [],
        ];

        // Add medication items
        foreach ($prescription->items as $item) {
            $resource['medicationReference'][] = [
                'reference' => 'Medication/' . $item->product_id,
                'display' => $item->product->productName ?? 'Unknown',
                'dosageInstruction' => [
                    'text' => $item->dosage ?? 'As directed',
                    'doseAndRate' => [
                        'doseQuantity' => [
                            'value' => $item->quantity,
                            'unit' => $item->unit ?? 'ea',
                        ],
                    ],
                ],
            ];
        }

        return $resource;
    }

    /**
     * Submit to FHIR system (simulated).
     *
     * @param array $fhirResource
     * @return array
     */
    protected function submitToFhirSystem(array $fhirResource): array
    {
        // In production, this would make actual API call to e-prescription system
        // For now, simulate the response
        
        $externalId = 'EP-' . strtoupper(uniqid());
        
        return [
            'success' => true,
            'external_id' => $externalId,
            'message' => 'Prescription submitted successfully',
        ];
    }

    /**
     * Get prescription status from FHIR system (simulated).
     *
     * @param string $externalId
     * @return array
     */
    protected function getPrescriptionStatusFromFhir(string $externalId): array
    {
        // In production, this would query the e-prescription system
        // For now, simulate the response
        
        return [
            'status' => 'active',
            'dispensed' => false,
            'verified' => true,
        ];
    }

    /**
     * Simulate FHIR validation.
     *
     * @param array $prescriptionData
     * @return array
     */
    protected function simulateFhirValidation(array $prescriptionData): array
    {
        $errors = [];
        $warnings = [];

        // Validate required fields
        if (empty($prescriptionData['patient_name'])) {
            $errors[] = 'Patient name is required';
        }

        if (empty($prescriptionData['doctor_name'])) {
            $errors[] = 'Doctor name is required';
        }

        if (empty($prescriptionData['items']) || count($prescriptionData['items']) === 0) {
            $errors[] = 'At least one medication item is required';
        }

        // Validate medication items
        foreach ($prescriptionData['items'] ?? [] as $item) {
            if (empty($item['product_id'])) {
                $errors[] = 'Product ID is required for all items';
            }

            if (empty($item['quantity']) || $item['quantity'] <= 0) {
                $errors[] = 'Valid quantity is required for all items';
            }

            if (empty($item['dosage'])) {
                $warnings[] = 'Dosage not specified for item';
            }
        }

        // Validate expiry date
        if (!empty($prescriptionData['expires_at'])) {
            $expiryDate = Carbon::parse($prescriptionData['expires_at']);
            if ($expiryDate->isPast()) {
                $errors[] = 'Prescription expiry date cannot be in the past';
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'external_id' => empty($errors) ? 'EP-' . strtoupper(uniqid()) : null,
        ];
    }

    /**
     * Search for patient in e-prescription system.
     *
     * @param string $identifier
     * @param int $businessId
     * @return array
     */
    public function searchPatient(string $identifier, int $businessId): array
    {
        // In production, this would search the national patient registry
        // For now, simulate the response
        
        return [
            'found' => true,
            'patient_id' => $identifier,
            'name' => 'John Doe',
            'date_of_birth' => '1980-01-01',
            'insurance_info' => [
                'provider' => 'Sample Insurance',
                'policy_number' => 'POL123456',
            ],
        ];
    }

    /**
     * Verify prescriber credentials.
     *
     * @param string $prescriberId
     * @param int $businessId
     * @return array
     */
    public function verifyPrescriber(string $prescriberId, int $businessId): array
    {
        // In production, this would verify against medical licensing database
        // For now, simulate the response
        
        return [
            'verified' => true,
            'name' => 'Dr. John Smith',
            'license_number' => 'MD12345',
            'license_expiry' => '2025-12-31',
            'specialty' => 'General Practice',
        ];
    }

    /**
     * Get available medications from e-prescription system.
     *
     * @param string $searchTerm
     * @param int $businessId
     * @return Collection
     */
    public function searchMedications(string $searchTerm, int $businessId): Collection
    {
        // In production, this would query the national drug database
        // For now, return local products
        
        return Product::where('business_id', $businessId)
            ->where('productName', 'like', "%{$searchTerm}%")
            ->limit(20)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->productName,
                    'code' => $product->productCode,
                    'form' => 'Tablet', // Would come from drug database
                    'strength' => '500mg', // Would come from drug database
                ];
            });
    }

    /**
     * Get prescription dispense history from e-prescription system.
     *
     * @param string $externalPrescriptionId
     * @return array
     */
    public function getDispenseHistory(string $externalPrescriptionId): array
    {
        // In production, this would query the e-prescription system
        // For now, simulate the response
        
        return [
            'prescription_id' => $externalPrescriptionId,
            'dispensed' => true,
            'dispensed_at' => now()->toIso8601String(),
            'dispensed_by' => 'Pharmacy-001',
            'items_dispensed' => [
                [
                    'medication' => 'Amoxicillin 500mg',
                    'quantity' => 30,
                    'batch_number' => 'BATCH001',
                ],
            ],
        ];
    }

    /**
     * Report adverse drug reaction to e-prescription system.
     *
     * @param array<string, mixed> $reportData
     * @param int $businessId
     * @return array
     */
    public function reportAdverseReaction(array $reportData, int $businessId): array
    {
        // In production, this would submit to pharmacovigilance system
        // For now, simulate the response
        
        $reportId = 'ADR-' . strtoupper(uniqid());
        
        return [
            'success' => true,
            'report_id' => $reportId,
            'message' => 'Adverse reaction reported successfully',
        ];
    }

    /**
     * Get e-prescription system status.
     *
     * @return array
     */
    public function getSystemStatus(): array
    {
        // In production, this would check the e-prescription system health
        // For now, simulate the response
        
        return [
            'status' => 'operational',
            'last_sync' => now()->toIso8601String(),
            'api_version' => '1.0.0',
            'maintenance_windows' => [],
        ];
    }
}