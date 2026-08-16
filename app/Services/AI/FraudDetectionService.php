<?php

namespace App\Services\AI;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Party;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FraudDetectionService
{
    use WithTransactionalOperations;

    /**
     * Analyze a sale for potential fraud.
     *
     * @param Sale $sale
     * @return array
     */
    public function analyzeSale(Sale $sale): array
    {
        $riskFactors = [];
        $totalRiskScore = 0;

        // Check 1: Unusual amount
        $amountRisk = $this->checkUnusualAmount($sale);
        if ($amountRisk['is_suspicious']) {
            $riskFactors[] = $amountRisk;
            $totalRiskScore += $amountRisk['risk_score'];
        }

        // Check 2: High frequency transactions
        $frequencyRisk = $this->checkTransactionFrequency($sale);
        if ($frequencyRisk['is_suspicious']) {
            $riskFactors[] = $frequencyRisk;
            $totalRiskScore += $frequencyRisk['risk_score'];
        }

        // Check 3: Unusual time pattern
        $timeRisk = $this->checkUnusualTimePattern($sale);
        if ($timeRisk['is_suspicious']) {
            $riskFactors[] = $timeRisk;
            $totalRiskScore += $timeRisk['risk_score'];
        }

        // Check 4: Customer behavior anomaly
        $behaviorRisk = $this->checkCustomerBehavior($sale);
        if ($behaviorRisk['is_suspicious']) {
            $riskFactors[] = $behaviorRisk;
            $totalRiskScore += $behaviorRisk['risk_score'];
        }

        // Check 5: Payment method anomaly
        $paymentRisk = $this->checkPaymentMethod($sale);
        if ($paymentRisk['is_suspicious']) {
            $riskFactors[] = $paymentRisk;
            $totalRiskScore += $paymentRisk['risk_score'];
        }

        // Check 6: Product combination anomaly
        $productRisk = $this->checkProductCombination($sale);
        if ($productRisk['is_suspicious']) {
            $riskFactors[] = $productRisk;
            $totalRiskScore += $productRisk['risk_score'];
        }

        // Determine overall risk level
        $riskLevel = $this->determineRiskLevel($totalRiskScore);

        return [
            'sale_id' => $sale->id,
            'risk_level' => $riskLevel,
            'risk_score' => $totalRiskScore,
            'risk_factors' => $riskFactors,
            'requires_review' => $riskLevel === 'high' || $riskLevel === 'critical',
            'recommended_action' => $this->getRecommendedAction($riskLevel),
        ];
    }

    /**
     * Check for unusual transaction amount.
     *
     * @param Sale $sale
     * @return array
     */
    protected function checkUnusualAmount(Sale $sale): array
    {
        $businessId = $sale->business_id;
        
        // Get average sale amount for this business
        $averageAmount = Sale::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->avg('totalAmount') ?? 0;

        if ($averageAmount == 0) {
            return ['is_suspicious' => false, 'risk_score' => 0];
        }

        $deviation = abs($sale->totalAmount - $averageAmount) / $averageAmount;

        if ($deviation > 5) { // More than 5x average
            return [
                'is_suspicious' => true,
                'risk_score' => 40,
                'type' => 'unusual_amount',
                'description' => "Sale amount is {$deviation}x the average",
                'average_amount' => $averageAmount,
                'current_amount' => $sale->totalAmount,
            ];
        }

        if ($deviation > 3) { // More than 3x average
            return [
                'is_suspicious' => true,
                'risk_score' => 20,
                'type' => 'unusual_amount',
                'description' => "Sale amount is {$deviation}x the average",
                'average_amount' => $averageAmount,
                'current_amount' => $sale->totalAmount,
            ];
        }

        return ['is_suspicious' => false, 'risk_score' => 0];
    }

    /**
     * Check for high frequency transactions.
     *
     * @param Sale $sale
     * @return array
     */
    protected function checkTransactionFrequency(Sale $sale): array
    {
        $businessId = $sale->business_id;
        $partyId = $sale->party_id;

        if (!$partyId) {
            return ['is_suspicious' => false, 'risk_score' => 0];
        }

        // Count transactions in last hour
        $recentTransactions = Sale::where('business_id', $businessId)
            ->where('party_id', $partyId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentTransactions > 10) {
            return [
                'is_suspicious' => true,
                'risk_score' => 50,
                'type' => 'high_frequency',
                'description' => "{$recentTransactions} transactions in the last hour",
                'recent_transactions' => $recentTransactions,
            ];
        }

        if ($recentTransactions > 5) {
            return [
                'is_suspicious' => true,
                'risk_score' => 25,
                'type' => 'high_frequency',
                'description' => "{$recentTransactions} transactions in the last hour",
                'recent_transactions' => $recentTransactions,
            ];
        }

        return ['is_suspicious' => false, 'risk_score' => 0];
    }

    /**
     * Check for unusual time pattern.
     *
     * @param Sale $sale
     * @return array
     */
    protected function checkUnusualTimePattern(Sale $sale): array
    {
        $hour = $sale->created_at->hour;

        // Suspicious if transaction at odd hours (2 AM - 5 AM)
        if ($hour >= 2 && $hour <= 5) {
            return [
                'is_suspicious' => true,
                'risk_score' => 30,
                'type' => 'unusual_time',
                'description' => "Transaction at {$hour}:00 - unusual time",
                'transaction_time' => $sale->created_at->toTimeString(),
            ];
        }

        return ['is_suspicious' => false, 'risk_score' => 0];
    }

    /**
     * Check customer behavior anomaly.
     *
     * @param Sale $sale
     * @return array
     */
    protected function checkCustomerBehavior(Sale $sale): array
    {
        $partyId = $sale->party_id;

        if (!$partyId) {
            return ['is_suspicious' => false, 'risk_score' => 0];
        }

        // Check if this is a new customer with large first purchase
        $previousSales = Sale::where('party_id', $partyId)->count();

        if ($previousSales == 0 && $sale->totalAmount > 1000) {
            return [
                'is_suspicious' => true,
                'risk_score' => 35,
                'type' => 'new_customer_large_purchase',
                'description' => "New customer with large first purchase",
                'purchase_amount' => $sale->totalAmount,
            ];
        }

        // Check for sudden increase in purchase amount
        if ($previousSales > 0) {
            $averageAmount = Sale::where('party_id', $partyId)
                ->avg('totalAmount') ?? 0;

            if ($averageAmount > 0 && $sale->totalAmount > $averageAmount * 10) {
                return [
                    'is_suspicious' => true,
                    'risk_score' => 40,
                    'type' => 'sudden_increase',
                    'description' => "Purchase amount is 10x customer average",
                    'average_amount' => $averageAmount,
                    'current_amount' => $sale->totalAmount,
                ];
            }
        }

        return ['is_suspicious' => false, 'risk_score' => 0];
    }

    /**
     * Check payment method anomaly.
     *
     * @param Sale $sale
     * @return array
     */
    protected function checkPaymentMethod(Sale $sale): array
    {
        $paymentType = $sale->paymentType;

        // Check if cash payment for large amount
        if ($paymentType === 'Cash' && $sale->totalAmount > 5000) {
            return [
                'is_suspicious' => true,
                'risk_score' => 25,
                'type' => 'large_cash_payment',
                'description' => "Large cash payment detected",
                'amount' => $sale->totalAmount,
            ];
        }

        return ['is_suspicious' => false, 'risk_score' => 0];
    }

    /**
     * Check product combination anomaly.
     *
     * @param Sale $sale
     * @return array
     */
    protected function checkProductCombination(Sale $sale): array
    {
        $details = $sale->details;

        // Check if unusual combination of products
        // This is a simplified check - in production, use ML-based pattern detection
        
        if ($details->count() > 20) {
            return [
                'is_suspicious' => true,
                'risk_score' => 20,
                'type' => 'large_product_count',
                'description' => "Unusually large number of products in single sale",
                'product_count' => $details->count(),
            ];
        }

        return ['is_suspicious' => false, 'risk_score' => 0];
    }

    /**
     * Determine risk level based on score.
     *
     * @param int $riskScore
     * @return string
     */
    public function determineRiskLevel(int $riskScore): string
    {
        if ($riskScore >= 100) {
            return 'critical';
        }
        if ($riskScore >= 60) {
            return 'high';
        }
        if ($riskScore >= 30) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Get recommended action based on risk level.
     *
     * @param string $riskLevel
     * @return string
     */
    public function getRecommendedAction(string $riskLevel): string
    {
        switch ($riskLevel) {
            case 'critical':
                return 'Block transaction and require manual review';
            case 'high':
                return 'Flag for manual review before processing';
            case 'medium':
                return 'Flag for review but allow processing';
            case 'low':
            default:
                return 'Process normally';
        }
    }

    /**
     * Analyze a purchase for potential fraud.
     *
     * @param Purchase $purchase
     * @return array
     */
    public function analyzePurchase(Purchase $purchase): array
    {
        $riskFactors = [];
        $totalRiskScore = 0;

        // Similar checks as sales but adapted for purchases
        $amountRisk = $this->checkPurchaseUnusualAmount($purchase);
        if ($amountRisk['is_suspicious']) {
            $riskFactors[] = $amountRisk;
            $totalRiskScore += $amountRisk['risk_score'];
        }

        $supplierRisk = $this->checkSupplierBehavior($purchase);
        if ($supplierRisk['is_suspicious']) {
            $riskFactors[] = $supplierRisk;
            $totalRiskScore += $supplierRisk['risk_score'];
        }

        $riskLevel = $this->determineRiskLevel($totalRiskScore);

        return [
            'purchase_id' => $purchase->id,
            'risk_level' => $riskLevel,
            'risk_score' => $totalRiskScore,
            'risk_factors' => $riskFactors,
            'requires_review' => $riskLevel === 'high' || $riskLevel === 'critical',
            'recommended_action' => $this->getRecommendedAction($riskLevel),
        ];
    }

    /**
     * Check purchase for unusual amount.
     *
     * @param Purchase $purchase
     * @return array
     */
    protected function checkPurchaseUnusualAmount(Purchase $purchase): array
    {
        $businessId = $purchase->business_id;
        
        $averageAmount = Purchase::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->avg('totalAmount') ?? 0;

        if ($averageAmount == 0) {
            return ['is_suspicious' => false, 'risk_score' => 0];
        }

        $deviation = abs($purchase->totalAmount - $averageAmount) / $averageAmount;

        if ($deviation > 5) {
            return [
                'is_suspicious' => true,
                'risk_score' => 35,
                'type' => 'unusual_amount',
                'description' => "Purchase amount is {$deviation}x the average",
            ];
        }

        return ['is_suspicious' => false, 'risk_score' => 0];
    }

    /**
     * Check supplier behavior.
     *
     * @param Purchase $purchase
     * @return array
     */
    protected function checkSupplierBehavior(Purchase $purchase): array
    {
        $partyId = $purchase->party_id;

        if (!$partyId) {
            return ['is_suspicious' => false, 'risk_score' => 0];
        }

        // Check if new supplier with large order
        $previousPurchases = Purchase::where('party_id', $partyId)->count();

        if ($previousPurchases == 0 && $purchase->totalAmount > 10000) {
            return [
                'is_suspicious' => true,
                'risk_score' => 30,
                'type' => 'new_supplier_large_order',
                'description' => "New supplier with large first order",
            ];
        }

        return ['is_suspicious' => false, 'risk_score' => 0];
    }

    /**
     * Get fraud analytics for a business.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getFraudAnalytics(int $businessId, array $filters = []): array
    {
        $query = Sale::where('business_id', $businessId);

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $sales = $query->get();

        $totalSales = $sales->count();
        $highRiskSales = 0;
        $totalRiskScore = 0;

        foreach ($sales as $sale) {
            $analysis = $this->analyzeSale($sale);
            if ($analysis['risk_level'] === 'high' || $analysis['risk_level'] === 'critical') {
                $highRiskSales++;
            }
            $totalRiskScore += $analysis['risk_score'];
        }

        return [
            'total_sales_analyzed' => $totalSales,
            'high_risk_sales' => $highRiskSales,
            'high_risk_percentage' => $totalSales > 0 ? ($highRiskSales / $totalSales) * 100 : 0,
            'average_risk_score' => $totalSales > 0 ? $totalRiskScore / $totalSales : 0,
            'risk_distribution' => $this->getRiskDistribution($sales),
        ];
    }

    /**
     * Get risk distribution.
     *
     * @param Collection $sales
     * @return array
     */
    protected function getRiskDistribution(Collection $sales): array
    {
        $distribution = [
            'low' => 0,
            'medium' => 0,
            'high' => 0,
            'critical' => 0,
        ];

        foreach ($sales as $sale) {
            $analysis = $this->analyzeSale($sale);
            $distribution[$analysis['risk_level']]++;
        }

        return $distribution;
    }
}