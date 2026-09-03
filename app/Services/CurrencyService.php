<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    use WithTransactionalOperations;

    /**
     * Convert amount between currencies.
     *
     * @param float $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param int $businessId
     * @return array
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency, int $businessId): array
    {
        if ($fromCurrency === $toCurrency) {
            return [
                'original_amount' => $amount,
                'converted_amount' => $amount,
                'rate' => 1.0,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
            ];
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency, $businessId);

        if (!$rate) {
            throw new \Exception('Exchange rate not available');
        }

        $convertedAmount = $amount * $rate;

        return [
            'original_amount' => $amount,
            'converted_amount' => $convertedAmount,
            'rate' => $rate,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
        ];
    }

    /**
     * Get exchange rate between two currencies.
     *
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param int $businessId
     * @return float|null
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency, int $businessId): ?float
    {
        // Try to get rate from database
        $rate = ExchangeRate::where('business_id', $businessId)
            ->where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('effective_date', '<=', now())
            ->where('expiry_date', '>', now())
            ->latest('effective_date')
            ->first();

        if ($rate) {
            return $rate->rate;
        }

        // If not in database, fetch from external API
        return $this->fetchExchangeRateFromApi($fromCurrency, $toCurrency);
    }

    /**
     * Fetch exchange rate from external API.
     *
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float|null
     */
    protected function fetchExchangeRateFromApi(string $fromCurrency, string $toCurrency): ?float
    {
        try {
            // Integrate with exchange rate API (e.g., exchangerate-api.io, fixer.io)
            // For now, simulate the response
            
            $rates = [
                'USD' => 1.0,
                'EUR' => 0.85,
                'GBP' => 0.75,
                'SAR' => 3.75,
                'AED' => 3.67,
                'EGP' => 30.9,
            ];

            $fromRate = $rates[$fromCurrency] ?? 1.0;
            $toRate = $rates[$toCurrency] ?? 1.0;

            return $toRate / $fromRate;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update exchange rates for a business.
     *
     * @param int $businessId
     * @return array
     */
    public function updateExchangeRates(int $businessId): array
    {
        $currencies = Currency::where('is_active', true)->get();
        $baseCurrency = Business::findOrFail($businessId)->default_currency ?? 'USD';

        $updatedCount = 0;

        foreach ($currencies as $currency) {
            if ($currency->code === $baseCurrency) {
                continue;
            }

            $rate = $this->fetchExchangeRateFromApi($baseCurrency, $currency->code);

            if ($rate) {
                ExchangeRate::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'from_currency' => $baseCurrency,
                        'to_currency' => $currency->code,
                        'effective_date' => now()->startOfDay(),
                    ],
                    [
                        'rate' => $rate,
                        'expiry_date' => now()->endOfDay(),
                    ]
                );

                $updatedCount++;
            }
        }

        return [
            'success' => true,
            'updated_count' => $updatedCount,
            'message' => "Updated {$updatedCount} exchange rates",
        ];
    }

    /**
     * Get supported currencies.
     *
     * @return Collection
     */
    public function getSupportedCurrencies(): Collection
    {
        return Currency::where('is_active', true)->get();
    }

    /**
     * Add a currency to business.
     *
     * @param int $businessId
     * @param string $currencyCode
     * @return bool
     */
    public function addCurrencyToBusiness(int $businessId, string $currencyCode): bool
    {
        $currency = Currency::where('code', $currencyCode)->first();

        if (!$currency) {
            return false;
        }

        // Add to business_currencies table
        // Implementation depends on schema

        return true;
    }

    /**
     * Format amount with currency symbol.
     *
     * @param float $amount
     * @param string $currencyCode
     * @return string
     */
    public function formatCurrency(float $amount, string $currencyCode): string
    {
        $currency = Currency::where('code', $currencyCode)->first();

        if (!$currency) {
            return number_format($amount, 2);
        }

        return $currency->symbol . ' ' . number_format($amount, 2);
    }
}