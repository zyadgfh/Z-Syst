<?php

namespace App\Services;

use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCoverage;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsuranceService
{
    /**
     * Create new insurance company
     */
    public function createCompany(array $data): InsuranceCompany
    {
        return DB::transaction(function () use ($data) {
            $data['code'] = $this->generateUniqueCompanyCode();
            return InsuranceCompany::create($data);
        });
    }

    /**
     * Update insurance company
     */
    public function updateCompany(InsuranceCompany $company, array $data): InsuranceCompany
    {
        $company->update($data);
        return $company->fresh();
    }

    /**
     * Create new insurance policy
     */
    public function createPolicy(array $data): InsurancePolicy
    {
        return DB::transaction(function () use ($data) {
            $data['policy_number'] = $this->generateUniquePolicyNumber();
            
            // Calculate remaining limit if annual limit is provided
            if (isset($data['annual_limit'])) {
                $data['remaining_limit'] = $data['annual_limit'];
            }

            return InsurancePolicy::create($data);
        });
    }

    /**
     * Update insurance policy
     */
    public function updatePolicy(InsurancePolicy $policy, array $data): InsurancePolicy
    {
        $policy->update($data);
        return $policy->fresh();
    }

    /**
     * Create insurance claim
     */
    public function createClaim(array $data): InsuranceClaim
    {
        return DB::transaction(function () use ($data) {
            $data['claim_number'] = $this->generateUniqueClaimNumber();
            
            // Auto-calculate coverage if not provided
            if (!isset($data['covered_amount']) || !isset($data['patient_responsibility'])) {
                $coverage = $this->calculateClaimCoverage($data);
                $data['covered_amount'] = $coverage['covered_amount'];
                $data['patient_responsibility'] = $coverage['patient_responsibility'];
            }

            $claim = InsuranceClaim::create($data);

            // Update policy used amount
            if ($claim->policy) {
                $claim->policy->increment('used_amount', $claim->covered_amount);
            }

            return $claim;
        });
    }

    /**
     * Submit claim to insurance company
     */
    public function submitClaim(InsuranceClaim $claim): InsuranceClaim
    {
        $claim->update([
            'status' => 'submitted',
            'submission_date' => now(),
        ]);

        // Here you would integrate with the insurance company's API
        // if integration_type is 'api' or 'hybrid'
        $this->notifyInsuranceCompany($claim);

        return $claim->fresh();
    }

    /**
     * Process claim approval/rejection
     */
    public function processClaim(InsuranceClaim $claim, array $data): InsuranceClaim
    {
        $status = $data['status'] ?? null;

        if (in_array($status, ['approved', 'partially_approved'], true)) {
            return $this->recordApproval(
                $claim,
                (float) ($data['approved_amount'] ?? 0),
                $data['external_reference'] ?? null
            );
        }

        if ($status === 'rejected') {
            return $this->rejectClaim($claim, (string) ($data['rejection_reason'] ?? 'Claim rejected.'));
        }

        throw new \DomainException('Unsupported claim processing status.');
    }

    /**
     * Record claim approval.
     */
    public function recordApproval(InsuranceClaim $claim, float $approvedAmount, ?string $externalReference = null): InsuranceClaim
    {
        return DB::transaction(function () use ($claim, $approvedAmount, $externalReference) {
            $claim->refresh();

            if (!in_array($claim->status, ['submitted', 'under_review'])) {
                throw new \DomainException('Only submitted claims can be approved.');
            }

            if ($approvedAmount < 0 || $approvedAmount > (float) $claim->covered_amount) {
                throw new \DomainException('Approved amount exceeds the covered claim amount.');
            }

            $status = $approvedAmount < (float) $claim->covered_amount
                ? 'partially_approved'
                : 'approved';

            $claim->update([
                'status' => $status,
                'approved_amount' => $approvedAmount,
                'rejected_amount' => max(0, (float) $claim->covered_amount - $approvedAmount),
                'external_reference' => $externalReference,
            ]);

            if ($claim->policy) {
                $policy = $claim->policy()->lockForUpdate()->firstOrFail();
                $difference = $approvedAmount - (float) $claim->covered_amount;

                if ($difference >= 0) {
                    $policy->increment('used_amount', $difference);
                } else {
                    $policy->decrement('used_amount', abs($difference));
                }
            }

            return $claim->fresh();
        });
    }

    /**
     * Reject a submitted claim.
     */
    public function rejectClaim(InsuranceClaim $claim, string $reason): InsuranceClaim
    {
        return DB::transaction(function () use ($claim, $reason) {
            $claim->refresh();

            if (!in_array($claim->status, ['submitted', 'under_review'])) {
                throw new \DomainException('Only submitted claims can be rejected.');
            }

            $claim->update([
                'status' => 'rejected',
                'approved_amount' => 0,
                'rejected_amount' => $claim->covered_amount,
                'rejection_reason' => $reason,
            ]);

            if ($claim->policy) {
                $policy = $claim->policy()->lockForUpdate()->firstOrFail();
                $policy->decrement('used_amount', min((float) $claim->covered_amount, (float) $policy->used_amount));
            }

            return $claim->fresh();
        });
    }

    /**
     * Record a payment against an approved claim.
     */
    public function recordPayment(InsuranceClaim $claim, float $paidAmount, ?\Carbon\Carbon $settlementDate = null): InsuranceClaim
    {
        return DB::transaction(function () use ($claim, $paidAmount, $settlementDate) {
            $claim->refresh();

            if (!$claim->isApproved()) {
                throw new \DomainException('Only approved claims can be paid.');
            }

            $approved = (float) ($claim->approved_amount ?? 0);
            $currentPaid = (float) ($claim->paid_amount ?? 0);

            if ($paidAmount <= 0 || $currentPaid + $paidAmount > $approved) {
                throw new \DomainException('Payment exceeds the approved claim amount.');
            }

            $newPaid = $currentPaid + $paidAmount;

            $fullyPaid = $newPaid >= $approved;

            $claim->update([
                'status' => $fullyPaid ? 'paid' : $claim->status,
                'paid_amount' => $newPaid,
                'settlement_date' => $fullyPaid ? ($settlementDate ?? now()) : $claim->settlement_date,
            ]);

            return $claim->fresh();
        });
    }

    /**
     * Process claim payment
     */
    public function processPayment(InsuranceClaim $claim, float $amount): InsuranceClaim
    {
        return $this->recordPayment($claim, $amount);
    }

    /**
     * Create coverage rule
     */
    public function createCoverage(array $data): InsuranceCoverage
    {
        return InsuranceCoverage::create($data);
    }

    /**
     * Update coverage rule
     */
    public function updateCoverage(InsuranceCoverage $coverage, array $data): InsuranceCoverage
    {
        $coverage->update($data);
        return $coverage->fresh();
    }

    /**
     * Calculate coverage for a claim
     */
    public function calculateClaimCoverage(array $data): array
    {
        $policy = InsurancePolicy::find($data['insurance_policy_id']);
        if (!$policy) {
            return [
                'covered_amount' => 0,
                'patient_responsibility' => $data['total_amount'],
            ];
        }

        // Get applicable coverage rules
        $coverageRules = $this->getApplicableCoverageRules($policy, $data);
        
        if ($coverageRules->isEmpty()) {
            // Use default company coverage
            return $policy->company->calculateDefaultCoverage($data['total_amount']);
        }

        // Apply best coverage rule
        $bestCoverage = $coverageRules->first();
        return $bestCoverage->calculateCoverage($data['total_amount']);
    }

    /**
     * Get applicable coverage rules for a claim
     */
    protected function getApplicableCoverageRules(InsurancePolicy $policy, array $data): \Illuminate\Database\Eloquent\Collection
    {
        $query = InsuranceCoverage::query()
            ->forCompany($policy->insurance_company_id)
            ->active();

        // If product_id is provided, try product-specific coverage
        if (isset($data['product_id'])) {
            $productCoverage = (clone $query)->forProduct($data['product_id'])->get();
            if ($productCoverage->isNotEmpty()) {
                return $productCoverage;
            }
        }

        // If category_id is provided, try category-specific coverage
        if (isset($data['category_id'])) {
            $categoryCoverage = (clone $query)->forCategory($data['category_id'])->get();
            if ($categoryCoverage->isNotEmpty()) {
                return $categoryCoverage;
            }
        }

        // Return general coverage
        return $query->where('scope', 'all')->get();
    }

    /**
     * Validate policy eligibility
     */
    public function validatePolicyEligibility(InsurancePolicy $policy, float $amount): array
    {
        if ($policy->isExpired()) {
            return [
                'eligible' => false,
                'reason' => 'Policy expired',
            ];
        }

        if (!$policy->hasSufficientLimit($amount)) {
            return [
                'eligible' => false,
                'reason' => 'Insufficient annual limit',
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
        ];
    }

    /**
     * Generate unique company code
     */
    protected function generateUniqueCompanyCode(): string
    {
        do {
            $code = 'INS-' . strtoupper(Str::random(8));
        } while (InsuranceCompany::where('code', $code)->exists());

        return $code;
    }

    /**
     * Generate unique policy number
     */
    protected function generateUniquePolicyNumber(): string
    {
        do {
            $number = 'POL-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (InsurancePolicy::where('policy_number', $number)->exists());

        return $number;
    }

    /**
     * Generate unique claim number
     */
    protected function generateUniqueClaimNumber(): string
    {
        do {
            $number = 'CLM-' . date('Ymd') . '-' . strtoupper(Str::random(8));
        } while (InsuranceClaim::where('claim_number', $number)->exists());

        return $number;
    }

    /**
     * Notify insurance company (placeholder for API integration)
     */
    protected function notifyInsuranceCompany(InsuranceClaim $claim): void
    {
        // Implement API integration based on company's integration_type
        // This would typically involve sending the claim data to the insurer's endpoint
    }

    /**
     * Get claim statistics for a business
     */
    public function getClaimStatistics(int $businessId, array $filters = []): array
    {
        $query = InsuranceClaim::forBusiness($businessId);

        if (isset($filters['date_from'])) {
            $query->where('service_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('service_date', '<=', $filters['date_to']);
        }

        if (isset($filters['company_id'])) {
            $query->where('insurance_company_id', $filters['company_id']);
        }

        $claims = $query->get();

        return [
            'total_claims' => $claims->count(),
            'total_amount' => $claims->sum('total_amount'),
            'total_covered' => $claims->sum('covered_amount'),
            'total_paid' => $claims->sum('paid_amount'),
            'pending_claims' => $claims->where('status', 'submitted')->count(),
            'approved_claims' => $claims->whereIn('status', ['approved', 'partially_approved'])->count(),
            'rejected_claims' => $claims->where('status', 'rejected')->count(),
            'paid_claims' => $claims->where('status', 'paid')->count(),
            'average_processing_days' => $this->calculateAverageProcessingDays($claims),
        ];
    }

    /**
     * Calculate average processing days for claims
     */
    protected function calculateAverageProcessingDays($claims): float
    {
        $processedClaims = $claims->filter(function ($claim) {
            return $claim->settlement_date && $claim->submission_date;
        });

        if ($processedClaims->isEmpty()) {
            return 0;
        }

        $totalDays = $processedClaims->sum(function ($claim) {
            return $claim->settlement_date->diffInDays($claim->submission_date);
        });

        return round($totalDays / $processedClaims->count(), 1);
    }
}
