<?php

namespace App\Services;

use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Models\InsurancePolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsuranceService
{
    /**
     * Generate a unique claim number for the business.
     */
    public function generateClaimNumber(int $businessId): string
    {
        $prefix = 'CLM-' . date('Ymd') . '-';
        $last = InsuranceClaim::where('business_id', $businessId)
            ->where('claim_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $last
            ? (int) substr($last->claim_number, -4) + 1
            : 1;

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique policy number for the business.
     */
    public function generatePolicyNumber(int $businessId): string
    {
        $prefix = 'POL-' . date('Ymd') . '-';
        $last = InsurancePolicy::where('business_id', $businessId)
            ->where('policy_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $last
            ? (int) substr($last->policy_number, -4) + 1
            : 1;

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a draft claim from a sale.
     *
     * @param  int  $saleId
     * @param  int  $policyId
     * @param  array<int, array<string, mixed>>  $lineItems
     */
    public function createClaimFromSale(int $businessId, int $saleId, int $policyId, array $lineItems = []): InsuranceClaim
    {
        return DB::transaction(function () use ($businessId, $saleId, $policyId, $lineItems) {
            $policy = InsurancePolicy::where('business_id', $businessId)
                ->findOrFail($policyId);

            if (! $policy->isValid()) {
                throw new \RuntimeException('Insurance policy is not currently valid.');
            }

            $sale = \App\Models\Sale::where('business_id', $businessId)
                ->findOrFail($saleId);

            $total = (float) $sale->total_amount;
            $coverage = $this->resolveCoverage($policy, $total);
            $covered = round($total * ($coverage / 100), 2);
            $patient = round($total - $covered, 2);

            return InsuranceClaim::create([
                'business_id' => $businessId,
                'insurance_company_id' => $policy->insurance_company_id,
                'insurance_policy_id' => $policy->id,
                'sale_id' => $sale->id,
                'customer_id' => $policy->customer_id,
                'user_id' => auth()->id(),
                'claim_number' => $this->generateClaimNumber($businessId),
                'service_date' => Carbon::now()->toDateString(),
                'total_amount' => $total,
                'covered_amount' => $covered,
                'patient_responsibility' => $patient,
                'status' => 'draft',
                'line_items' => $lineItems,
            ]);
        });
    }

    /**
     * Submit a draft claim. Moves status from draft -> submitted.
     */
    public function submitClaim(InsuranceClaim $claim): InsuranceClaim
    {
        if ($claim->status !== 'draft') {
            throw new \RuntimeException("Claim in status '{$claim->status}' cannot be submitted.");
        }

        $claim->update([
            'status' => 'submitted',
            'submission_date' => Carbon::now()->toDateString(),
        ]);

        Log::info('insurance.claim.submitted', [
            'claim_id' => $claim->id,
            'claim_number' => $claim->claim_number,
        ]);

        return $claim->fresh();
    }

    /**
     * Record insurer approval and update policy utilization.
     */
    public function recordApproval(InsuranceClaim $claim, float $approvedAmount, ?string $externalRef = null): InsuranceClaim
    {
        if (! in_array($claim->status, ['submitted', 'under_review'], true)) {
            throw new \RuntimeException("Claim in status '{$claim->status}' cannot be approved.");
        }

        $approvedAmount = max(0.0, $approvedAmount);

        return DB::transaction(function () use ($claim, $approvedAmount, $externalRef) {
            $claim->update([
                'status' => $approvedAmount >= (float) $claim->total_amount
                    ? 'approved'
                    : 'partially_approved',
                'approved_amount' => $approvedAmount,
                'rejected_amount' => round((float) $claim->total_amount - $approvedAmount, 2),
                'external_reference' => $externalRef,
            ]);

            $policy = $claim->insurancePolicy;
            $policy->used_amount = (float) $policy->used_amount + $approvedAmount;
            if ($policy->annual_limit !== null) {
                $policy->remaining_limit = (float) $policy->annual_limit - (float) $policy->used_amount;
            }
            $policy->save();

            return $claim->fresh();
        });
    }

    /**
     * Reject a claim with a reason.
     */
    public function rejectClaim(InsuranceClaim $claim, string $reason): InsuranceClaim
    {
        if (! in_array($claim->status, ['submitted', 'under_review'], true)) {
            throw new \RuntimeException("Claim in status '{$claim->status}' cannot be rejected.");
        }

        $claim->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_amount' => $claim->total_amount,
        ]);

        return $claim->fresh();
    }

    /**
     * Mark a claim as paid by the insurer.
     */
    public function recordPayment(InsuranceClaim $claim, float $paidAmount, ?Carbon $settlementDate = null): InsuranceClaim
    {
        if (! in_array($claim->status, ['approved', 'partially_approved'], true)) {
            throw new \RuntimeException("Claim in status '{$claim->status}' cannot be marked as paid.");
        }

        $claim->update([
            'status' => 'paid',
            'paid_amount' => $paidAmount,
            'settlement_date' => ($settlementDate ?? Carbon::now())->toDateString(),
        ]);

        return $claim->fresh();
    }

    /**
     * Resolve the coverage percentage that applies to a given amount,
     * honoring company defaults, policy overrides, and per-product rules.
     */
    public function resolveCoverage(InsurancePolicy $policy, float $amount, ?int $productId = null, ?int $categoryId = null): float
    {
        $company = $policy->insuranceCompany;

        // Per-product/per-category coverage wins if it matches and is active.
        if ($productId) {
            $rule = InsuranceCoverage::where('insurance_company_id', $company->id)
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('effective_from')->orWhere('effective_from', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
                })
                ->orderByDesc('id')
                ->first();

            if ($rule) {
                return (float) $rule->coverage_percent;
            }
        }

        if ($categoryId) {
            $rule = InsuranceCoverage::where('insurance_company_id', $company->id)
                ->where('category_id', $categoryId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();

            if ($rule) {
                return (float) $rule->coverage_percent;
            }
        }

        // Policy-level override
        if ($policy->coverage_percent !== null) {
            return (float) $policy->coverage_percent;
        }

        // Company default
        return (float) $company->default_coverage_percent;
    }

    /**
     * Dashboard summary for a business.
     *
     * @return array<string, mixed>
     */
    public function getSummary(int $businessId): array
    {
        $companyCount = InsuranceCompany::where('business_id', $businessId)->count();
        $activeCompanyCount = InsuranceCompany::where('business_id', $businessId)
            ->where('status', 'active')->count();
        $policyCount = InsurancePolicy::where('business_id', $businessId)->count();
        $activePolicyCount = InsurancePolicy::where('business_id', $businessId)
            ->where('status', 'active')->count();
        $expiringPolicyCount = InsurancePolicy::where('business_id', $businessId)
            ->expiringSoon(30)->count();

        $claimStats = InsuranceClaim::where('business_id', $businessId)
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(total_amount),0) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(fn ($row) => ['count' => (int) $row->count, 'total' => (float) $row->total])
            ->all();

        $pending = ($claimStats['submitted']['total'] ?? 0) + ($claimStats['under_review']['total'] ?? 0);
        $paid = $claimStats['paid']['total'] ?? 0;

        return [
            'companies' => [
                'total' => $companyCount,
                'active' => $activeCompanyCount,
            ],
            'policies' => [
                'total' => $policyCount,
                'active' => $activePolicyCount,
                'expiring_soon' => $expiringPolicyCount,
            ],
            'claims' => [
                'by_status' => $claimStats,
                'pending_amount' => (float) $pending,
                'paid_amount' => (float) $paid,
            ],
        ];
    }
}
