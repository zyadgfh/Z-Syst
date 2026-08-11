<?php

namespace App\Services;

use App\Models\Category;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Models\InsurancePolicy;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Party;
use Illuminate\Database\Eloquent\Collection;
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
            if (! isset($data['covered_amount']) || ! isset($data['patient_responsibility'])) {
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
        return DB::transaction(function () use ($claim, $data) {
            $claim->update([
                'status' => $data['status'],
                'approved_amount' => $data['approved_amount'] ?? 0,
                'rejected_amount' => $data['rejected_amount'] ?? 0,
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
            ]);

            // Update policy used amount based on approval
            if ($claim->policy && $claim->isApproved()) {
                $difference = $claim->approved_amount - $claim->covered_amount;
                $claim->policy->increment('used_amount', $difference);
            }

            return $claim->fresh();
        });
    }

    /**
     * Process claim payment
     */
    public function processPayment(InsuranceClaim $claim, float $amount): InsuranceClaim
    {
        return DB::transaction(function () use ($claim, $amount) {
            $claim->update([
                'status' => 'paid',
                'paid_amount' => $claim->paid_amount + $amount,
                'settlement_date' => now(),
            ]);

            return $claim->fresh();
        });
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
        if (! $policy) {
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
    protected function getApplicableCoverageRules(InsurancePolicy $policy, array $data): Collection
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

        if (! $policy->hasSufficientLimit($amount)) {
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
            $code = 'INS-'.strtoupper(Str::random(8));
        } while (InsuranceCompany::where('code', $code)->exists());

        return $code;
    }

    /**
     * Generate unique policy number
     */
    protected function generateUniquePolicyNumber(): string
    {
        do {
            $number = 'POL-'.date('Ymd').'-'.strtoupper(Str::random(6));
        } while (InsurancePolicy::where('policy_number', $number)->exists());

        return $number;
    }

    /**
     * Generate unique claim number
     */
    protected function generateUniqueClaimNumber(): string
    {
        do {
            $number = 'CLM-'.date('Ymd').'-'.strtoupper(Str::random(8));
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

    /**
     * Process insurance claim from sale.
     *
     * @param Sale $sale
     * @param array<string, mixed> $claimData
     * @return InsuranceClaim
     * @throws \Exception
     */
    public function processInsuranceClaimFromSale(Sale $sale, array $claimData): InsuranceClaim
    {
        return DB::transaction(function () use ($sale, $claimData) {
            // Validate that the customer has an insurance policy
            if (!$sale->party_id) {
                throw new \Exception('Sale must have a customer to process insurance claim');
            }

            $customer = Party::findOrFail($sale->party_id);
            $policy = InsurancePolicy::where('party_id', $customer->id)
                ->where('business_id', $sale->business_id)
                ->where('status', 'active')
                ->first();

            if (!$policy) {
                throw new \Exception('No active insurance policy found for this customer');
            }

            // Validate policy eligibility
            $eligibility = $this->validatePolicyEligibility($policy, $sale->totalAmount);
            if (!$eligibility['eligible']) {
                throw new \Exception('Policy not eligible: ' . $eligibility['reason']);
            }

            // Calculate claim coverage for each item
            $claimItems = [];
            $totalClaimAmount = 0;

            foreach ($sale->details as $detail) {
                $itemCoverage = $this->calculateClaimCoverage([
                    'insurance_policy_id' => $policy->id,
                    'product_id' => $detail->product_id,
                    'total_amount' => $detail->price * $detail->quantities,
                ]);

                $claimItems[] = [
                    'sale_detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'quantity' => $detail->quantities,
                    'unit_price' => $detail->price,
                    'total_amount' => $detail->price * $detail->quantities,
                    'covered_amount' => $itemCoverage['covered_amount'],
                    'patient_responsibility' => $itemCoverage['patient_responsibility'],
                ];

                $totalClaimAmount += $itemCoverage['covered_amount'];
            }

            // Create insurance claim
            $claim = InsuranceClaim::create([
                'business_id' => $sale->business_id,
                'insurance_policy_id' => $policy->id,
                'party_id' => $customer->id,
                'sale_id' => $sale->id,
                'claim_number' => $this->generateUniqueClaimNumber(),
                'total_amount' => $sale->totalAmount,
                'covered_amount' => $totalClaimAmount,
                'patient_responsibility' => $sale->totalAmount - $totalClaimAmount,
                'status' => 'draft',
                'submission_date' => null,
                'items' => json_encode($claimItems),
                'notes' => $claimData['notes'] ?? 'Claim generated from sale #' . $sale->invoiceNumber,
            ]);

            // Update policy used amount
            $policy->increment('used_amount', $totalClaimAmount);

            return $claim->fresh(['policy.company', 'party', 'sale']);
        });
    }

    /**
     * Validate insurance coverage for sale items.
     *
     * @param array<int, array> $saleItems
     * @param int $policyId
     * @return array
     */
    public function validateInsuranceCoverage(array $saleItems, int $policyId): array
    {
        $policy = InsurancePolicy::findOrFail($policyId);
        $validationResults = [];

        foreach ($saleItems as $item) {
            $coverage = $this->calculateClaimCoverage([
                'insurance_policy_id' => $policyId,
                'product_id' => $item['product_id'],
                'total_amount' => $item['unit_price'] * $item['quantity'],
            ]);

            $validationResults[] = [
                'product_id' => $item['product_id'],
                'product_name' => Product::find($item['product_id'])?->productName ?? 'Unknown',
                'total_amount' => $item['unit_price'] * $item['quantity'],
                'covered_amount' => $coverage['covered_amount'],
                'patient_responsibility' => $coverage['patient_responsibility'],
                'coverage_percentage' => $coverage['covered_amount'] > 0 
                    ? ($coverage['covered_amount'] / ($item['unit_price'] * $item['quantity'])) * 100 
                    : 0,
                'is_covered' => $coverage['covered_amount'] > 0,
            ];
        }

        return [
            'policy' => $policy,
            'items' => $validationResults,
            'total_claim_amount' => collect($validationResults)->sum('covered_amount'),
            'total_patient_responsibility' => collect($validationResults)->sum('patient_responsibility'),
            'eligibility' => $this->validatePolicyEligibility($policy, collect($validationResults)->sum('total_amount')),
        ];
    }

    /**
     * Calculate insurance reimbursement for a claim.
     *
     * @param array<string, mixed> $claimData
     * @return array
     */
    public function calculateInsuranceReimbursement(array $claimData): array
    {
        $policy = InsurancePolicy::findOrFail($claimData['insurance_policy_id']);
        $totalAmount = $claimData['total_amount'];
        
        // Get applicable coverage rules
        $coverageRules = $this->getApplicableCoverageRules($policy, $claimData);
        
        if ($coverageRules->isEmpty()) {
            // Use default company coverage
            $coverage = $policy->company->calculateDefaultCoverage($totalAmount);
        } else {
            // Apply best coverage rule
            $bestCoverage = $coverageRules->first();
            $coverage = $bestCoverage->calculateCoverage($totalAmount);
        }

        // Check policy limits
        $remainingLimit = $policy->annual_limit - $policy->used_amount;
        $maxReimbursement = min($coverage['covered_amount'], $remainingLimit);

        return [
            'total_amount' => $totalAmount,
            'covered_amount' => $coverage['covered_amount'],
            'patient_responsibility' => $coverage['patient_responsibility'],
            'policy_limit' => $policy->annual_limit,
            'policy_used' => $policy->used_amount,
            'policy_remaining' => $remainingLimit,
            'max_reimbursement' => $maxReimbursement,
            'reimbursement_percentage' => $totalAmount > 0 ? ($maxReimbursement / $totalAmount) * 100 : 0,
        ];
    }

    /**
     * Get customer insurance policies.
     *
     * @param int $customerId
     * @param int $businessId
     * @return Collection
     */
    public function getCustomerPolicies(int $customerId, int $businessId): Collection
    {
        return InsurancePolicy::where('party_id', $customerId)
            ->where('business_id', $businessId)
            ->with(['company', 'coverages'])
            ->get()
            ->map(function ($policy) {
                return [
                    'policy' => $policy,
                    'company' => $policy->company,
                    'is_active' => $policy->status === 'active' && !$policy->isExpired(),
                    'remaining_limit' => $policy->annual_limit - $policy->used_amount,
                    'utilization_percentage' => $policy->annual_limit > 0 
                        ? ($policy->used_amount / $policy->annual_limit) * 100 
                        : 0,
                ];
            });
    }

    /**
     * Auto-submit eligible claims from sales.
     *
     * @param int $businessId
     * @param array<int> $saleIds
     * @return array
     */
    public function autoSubmitClaims(int $businessId, array $saleIds = []): array
    {
        $query = Sale::where('business_id', $businessId)
            ->whereNotNull('party_id')
            ->whereDoesntHave('insuranceClaim');

        if (!empty($saleIds)) {
            $query->whereIn('id', $saleIds);
        }

        $sales = $query->get();
        $submittedClaims = [];
        $failedClaims = [];

        foreach ($sales as $sale) {
            try {
                $claim = $this->processInsuranceClaimFromSale($sale, [
                    'notes' => 'Auto-submitted claim from sale',
                ]);
                
                // Auto-submit if configured
                $this->submitClaim($claim);
                
                $submittedClaims[] = [
                    'sale_id' => $sale->id,
                    'claim_id' => $claim->id,
                    'claim_number' => $claim->claim_number,
                ];
            } catch (\Exception $e) {
                $failedClaims[] = [
                    'sale_id' => $sale->id,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return [
            'total_processed' => $sales->count(),
            'successful_submissions' => count($submittedClaims),
            'failed_submissions' => count($failedClaims),
            'submitted_claims' => $submittedClaims,
            'failed_claims' => $failedClaims,
        ];
    }
}
