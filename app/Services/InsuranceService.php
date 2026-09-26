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
            $businessId = (int) ($data['business_id'] ?? (app()->bound('tenant_id') ? app('tenant_id') : 0));
            if ($businessId <= 0) {
                throw new \InvalidArgumentException('Business context is required.');
            }

            return InsuranceCompany::create([
                'business_id' => $businessId,
                'name' => $data['name'],
                'code' => $this->generateUniqueCompanyCode(),
                'contact_person' => $data['contact_person'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'status' => $data['status'] ?? 'active',
                'integration_type' => $data['integration_type'] ?? 'manual',
                'api_endpoint' => $data['api_endpoint'] ?? null,
                'api_credentials' => $data['api_credentials'] ?? null,
                'default_coverage_percent' => $data['default_coverage_percent'] ?? 0,
                'default_copay_percent' => $data['default_copay_percent'] ?? 0,
                'settlement_days' => $data['settlement_days'] ?? 1,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);
        });
    }

    /**
     * Update insurance company
     */
    public function updateCompany(InsuranceCompany $company, array $data): InsuranceCompany
    {
        $company->update([
            'name' => $data['name'] ?? $company->name,
            'contact_person' => $data['contact_person'] ?? $company->contact_person,
            'phone' => $data['phone'] ?? $company->phone,
            'email' => $data['email'] ?? $company->email,
            'address' => $data['address'] ?? $company->address,
            'city' => $data['city'] ?? $company->city,
            'country' => $data['country'] ?? $company->country,
            'tax_id' => $data['tax_id'] ?? $company->tax_id,
            'status' => $data['status'] ?? $company->status,
            'integration_type' => $data['integration_type'] ?? $company->integration_type,
            'api_endpoint' => $data['api_endpoint'] ?? $company->api_endpoint,
            'api_credentials' => $data['api_credentials'] ?? $company->api_credentials,
            'default_coverage_percent' => $data['default_coverage_percent'] ?? $company->default_coverage_percent,
            'default_copay_percent' => $data['default_copay_percent'] ?? $company->default_copay_percent,
            'settlement_days' => $data['settlement_days'] ?? $company->settlement_days,
            'notes' => $data['notes'] ?? $company->notes,
            'metadata' => $data['metadata'] ?? $company->metadata,
        ]);

        return $company->fresh();
    }

    /**
     * Create new insurance policy
     */
    public function createPolicy(array $data): InsurancePolicy
    {
        return DB::transaction(function () use ($data) {
            $businessId = (int) ($data['business_id'] ?? (app()->bound('tenant_id') ? app('tenant_id') : 0));
            if ($businessId <= 0) {
                throw new \InvalidArgumentException('Business context is required.');
            }

            InsuranceCompany::where('business_id', $businessId)->findOrFail($data['insurance_company_id']);
            if (isset($data['customer_id'])) {
                \App\Models\Party::where('business_id', $businessId)->findOrFail($data['customer_id']);
            }

            $annualLimit = isset($data['annual_limit']) ? (float) $data['annual_limit'] : 0;

            return InsurancePolicy::create([
                'business_id' => $businessId,
                'insurance_company_id' => $data['insurance_company_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'policy_number' => $this->generateUniquePolicyNumber(),
                'member_id' => $data['member_id'] ?? null,
                'card_number' => $data['card_number'] ?? null,
                'holder_name' => $data['holder_name'],
                'holder_dob' => $data['holder_dob'] ?? null,
                'holder_gender' => $data['holder_gender'] ?? null,
                'holder_phone' => $data['holder_phone'] ?? null,
                'holder_email' => $data['holder_email'] ?? null,
                'holder_address' => $data['holder_address'] ?? null,
                'plan_type' => $data['plan_type'],
                'status' => $data['status'] ?? 'pending',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'annual_limit' => $annualLimit,
                'used_amount' => 0,
                'remaining_limit' => $annualLimit,
                'coverage_percent' => $data['coverage_percent'] ?? null,
                'copay_percent' => $data['copay_percent'] ?? null,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);
        });
    }

    /**
     * Update insurance policy
     */
    public function updatePolicy(InsurancePolicy $policy, array $data): InsurancePolicy
    {
        if (isset($data['insurance_company_id'])) {
            InsuranceCompany::where('business_id', $policy->business_id)->findOrFail($data['insurance_company_id']);
        }

        if (isset($data['customer_id'])) {
            \App\Models\Party::where('business_id', $policy->business_id)->findOrFail($data['customer_id']);
        }

        $policy->update([
            'insurance_company_id' => $data['insurance_company_id'] ?? $policy->insurance_company_id,
            'customer_id' => $data['customer_id'] ?? $policy->customer_id,
            'member_id' => $data['member_id'] ?? $policy->member_id,
            'card_number' => $data['card_number'] ?? $policy->card_number,
            'holder_name' => $data['holder_name'] ?? $policy->holder_name,
            'holder_dob' => $data['holder_dob'] ?? $policy->holder_dob,
            'holder_gender' => $data['holder_gender'] ?? $policy->holder_gender,
            'holder_phone' => $data['holder_phone'] ?? $policy->holder_phone,
            'holder_email' => $data['holder_email'] ?? $policy->holder_email,
            'holder_address' => $data['holder_address'] ?? $policy->holder_address,
            'plan_type' => $data['plan_type'] ?? $policy->plan_type,
            'status' => $data['status'] ?? $policy->status,
            'start_date' => $data['start_date'] ?? $policy->start_date,
            'end_date' => $data['end_date'] ?? $policy->end_date,
            'annual_limit' => $data['annual_limit'] ?? $policy->annual_limit,
            'coverage_percent' => $data['coverage_percent'] ?? $policy->coverage_percent,
            'copay_percent' => $data['copay_percent'] ?? $policy->copay_percent,
            'notes' => $data['notes'] ?? $policy->notes,
            'metadata' => $data['metadata'] ?? $policy->metadata,
        ]);

        return $policy->fresh();
    }

    /**
     * Create insurance claim
     */
    public function createClaim(array $data): InsuranceClaim
    {
        return DB::transaction(function () use ($data) {
            $businessId = (int) ($data['business_id'] ?? (app()->bound('tenant_id') ? app('tenant_id') : 0));
            if ($businessId <= 0) {
                throw new \InvalidArgumentException('Business context is required.');
            }

            $policy = InsurancePolicy::where('business_id', $businessId)->findOrFail($data['insurance_policy_id']);
            InsuranceCompany::where('business_id', $businessId)->findOrFail($data['insurance_company_id']);

            if (isset($data['sale_id'])) {
                \App\Models\Sale::where('business_id', $businessId)->findOrFail($data['sale_id']);
            }
            if (isset($data['prescription_id'])) {
                \App\Models\Prescription::where('business_id', $businessId)->findOrFail($data['prescription_id']);
            }
            if (isset($data['customer_id'])) {
                \App\Models\Party::where('business_id', $businessId)->findOrFail($data['customer_id']);
            }

            $claimData = [
                'business_id' => $businessId,
                'insurance_company_id' => $data['insurance_company_id'],
                'insurance_policy_id' => $policy->id,
                'sale_id' => $data['sale_id'] ?? null,
                'prescription_id' => $data['prescription_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => auth()->id(),
                'claim_number' => $this->generateUniqueClaimNumber(),
                'service_date' => $data['service_date'],
                'total_amount' => $data['total_amount'],
                'covered_amount' => $data['covered_amount'] ?? null,
                'patient_responsibility' => $data['patient_responsibility'] ?? null,
                'approved_amount' => 0,
                'paid_amount' => 0,
                'rejected_amount' => 0,
                'status' => 'draft',
                'submission_date' => null,
                'rejection_reason' => null,
                'external_reference' => null,
                'settlement_date' => null,
                'notes' => $data['notes'] ?? null,
                'line_items' => $data['line_items'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ];

            if ($claimData['covered_amount'] === null || $claimData['patient_responsibility'] === null) {
                $coverage = $this->calculateClaimCoverage($claimData);
                $claimData['covered_amount'] = $coverage['covered_amount'];
                $claimData['patient_responsibility'] = $coverage['patient_responsibility'];
            }

            if ((float) $claimData['covered_amount'] > (float) $claimData['total_amount']
                || (float) $claimData['patient_responsibility'] > (float) $claimData['total_amount']) {
                throw new \DomainException('Claim allocation cannot exceed total amount.');
            }

            return InsuranceClaim::create($claimData);
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
                $previousApproved = (float) ($claim->getOriginal('approved_amount') ?? 0);
                $difference = $approvedAmount - $previousApproved;

                if ($difference > 0) {
                    $policy->increment('used_amount', $difference);
                } elseif ($difference < 0) {
                    $policy->decrement('used_amount', min(abs($difference), (float) $policy->used_amount));
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
                $previousApproved = (float) ($claim->getOriginal('approved_amount') ?? 0);
                $policy->decrement('used_amount', min($previousApproved, (float) $policy->used_amount));
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
