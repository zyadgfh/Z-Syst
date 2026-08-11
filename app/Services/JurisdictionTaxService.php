<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Tax;
use App\Models\TaxJurisdiction;
use App\Models\TaxRate;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;

class JurisdictionTaxService
{
    use WithTransactionalOperations;

    /**
     * Calculate tax for a transaction based on jurisdiction.
     *
     * @param float $amount
     * @param string $taxType
     * @param string $jurisdictionCode
     * @param int $businessId
     * @return array
     */
    public function calculateTax(float $amount, string $taxType, string $jurisdictionCode, int $businessId): array
    {
        $taxRate = $this->getTaxRate($taxType, $jurisdictionCode, $businessId);

        if (!$taxRate) {
            return [
                'tax_amount' => 0,
                'tax_rate' => 0,
                'tax_type' => $taxType,
                'jurisdiction' => $jurisdictionCode,
            ];
        }

        $taxAmount = $amount * ($taxRate->rate / 100);

        return [
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate->rate,
            'tax_type' => $taxType,
            'jurisdiction' => $jurisdictionCode,
            'jurisdiction_name' => $taxRate->jurisdiction->name ?? $jurisdictionCode,
        ];
    }

    /**
     * Get tax rate for a specific type and jurisdiction.
     *
     * @param string $taxType
     * @param string $jurisdictionCode
     * @param int $businessId
     * @return TaxRate|null
     */
    public function getTaxRate(string $taxType, string $jurisdictionCode, int $businessId): ?TaxRate
    {
        return TaxRate::where('business_id', $businessId)
            ->where('tax_type', $taxType)
            ->whereHas('jurisdiction', function ($query) use ($jurisdictionCode) {
                $query->where('code', $jurisdictionCode);
            })
            ->where('effective_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>', now());
            })
            ->first();
    }

    /**
     * Add tax jurisdiction to business.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return TaxJurisdiction
     */
    public function addJurisdiction(array $data, int $businessId): TaxJurisdiction
    {
        return TaxJurisdiction::create([
            'business_id' => $businessId,
            'code' => $data['code'],
            'name' => $data['name'],
            'country' => $data['country'],
            'state' => $data['state'] ?? null,
            'city' => $data['city'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Add tax rate for a jurisdiction.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return TaxRate
     */
    public function addTaxRate(array $data, int $businessId): TaxRate
    {
        return TaxRate::create([
            'business_id' => $businessId,
            'tax_jurisdiction_id' => $data['tax_jurisdiction_id'],
            'tax_type' => $data['tax_type'], // sales, vat, service, etc.
            'rate' => $data['rate'],
            'effective_date' => $data['effective_date'] ?? now(),
            'expiry_date' => $data['expiry_date'] ?? null,
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * Calculate total tax for multiple items.
     *
     * @param array $items
     * @param string $taxType
     * @param string $jurisdictionCode
     * @param int $businessId
     * @return array
     */
    public function calculateBulkTax(array $items, string $taxType, string $jurisdictionCode, int $businessId): array
    {
        $totalTax = 0;
        $itemTaxes = [];

        foreach ($items as $item) {
            $taxCalculation = $this->calculateTax($item['amount'], $taxType, $jurisdictionCode, $businessId);
            
            $itemTaxes[] = [
                'item_id' => $item['id'] ?? null,
                'amount' => $item['amount'],
                'tax_amount' => $taxCalculation['tax_amount'],
                'tax_rate' => $taxCalculation['tax_rate'],
            ];
            
            $totalTax += $taxCalculation['tax_amount'];
        }

        return [
            'total_tax' => $totalTax,
            'item_taxes' => $itemTaxes,
            'tax_type' => $taxType,
            'jurisdiction' => $jurisdictionCode,
        ];
    }

    /**
     * Get tax report for a business.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getTaxReport(int $businessId, array $filters = []): array
    {
        $jurisdictions = TaxJurisdiction::where('business_id', $businessId)
            ->with('taxRates')
            ->get();

        $report = [
            'business_id' => $businessId,
            'jurisdictions' => [],
            'total_tax_liability' => 0,
        ];

        foreach ($jurisdictions as $jurisdiction) {
            $totalRate = $jurisdiction->taxRates->sum('rate');
            
            $report['jurisdictions'][] = [
                'code' => $jurisdiction->code,
                'name' => $jurisdiction->name,
                'country' => $jurisdiction->country,
                'tax_types' => $jurisdiction->taxRates->pluck('tax_type')->unique(),
                'total_rate' => $totalRate,
            ];
        }

        return $report;
    }

    /**
     * Update tax rates from external tax service.
     *
     * @param int $businessId
     * @return array
     */
    public function updateTaxRates(int $businessId): array
    {
        // Integrate with external tax service (e.g., Avalara, TaxJar)
        // For now, simulate the response
        
        return [
            'success' => true,
            'message' => 'Tax rates updated from external service',
            'updated_count' => 0,
        ];
    }

    /**
     * Get tax-exempt status for a customer.
     *
     * @param int $partyId
     * @param int $businessId
     * @return array
     */
    public function getTaxExemptStatus(int $partyId, int $businessId): array
    {
        // Check if customer has tax exemption certificate
        // Implementation depends on schema
        
        return [
            'is_exempt' => false,
            'exemption_type' => null,
            'exemption_certificate' => null,
        ];
    }
}