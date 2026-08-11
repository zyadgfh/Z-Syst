<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ChurnPredictionService
{
    /**
     * Predict churn risk for a customer.
     *
     * @param int $customerId
     * @param int $businessId
     * @return array
     */
    public function predictChurnRisk(int $customerId, int $businessId): array
    {
        $customer = Customer::findOrFail($customerId);
        
        $riskFactors = [];
        $totalRiskScore = 0;

        // Factor 1: Decreasing purchase frequency
        $frequencyRisk = $this->checkPurchaseFrequency($customer, $businessId);
        if ($frequencyRisk['is_risk']) {
            $riskFactors[] = $frequencyRisk;
            $totalRiskScore += $frequencyRisk['risk_score'];
        }

        // Factor 2: Decreasing purchase amount
        $amountRisk = $this->checkPurchaseAmount($customer, $businessId);
        if ($amountRisk['is_risk']) {
            $riskFactors[] = $amountRisk;
            $totalRiskScore += $amountRisk['risk_score'];
        }

        // Factor 3: Last purchase recency
        $recencyRisk = $this->checkPurchaseRecency($customer, $businessId);
        if ($recencyRisk['is_risk']) {
            $riskFactors[] = $recencyRisk;
            $totalRiskScore += $recencyRisk['risk_score'];
        }

        // Factor 4: Subscription status
        $subscriptionRisk = $this->checkSubscriptionStatus($customer, $businessId);
        if ($subscriptionRisk['is_risk']) {
            $riskFactors[] = $subscriptionRisk;
            $totalRiskScore += $subscriptionRisk['risk_score'];
        }

        // Factor 5: Support ticket frequency
        $supportRisk = $this->checkSupportActivity($customer, $businessId);
        if ($supportRisk['is_risk']) {
            $riskFactors[] = $supportRisk;
            $totalRiskScore += $supportRisk['risk_score'];
        }

        $riskLevel = $this->determineRiskLevel($totalRiskScore);

        return [
            'customer_id' => $customerId,
            'risk_level' => $riskLevel,
            'risk_score' => $totalRiskScore,
            'risk_factors' => $riskFactors,
            'recommended_action' => $this->getRecommendedAction($riskLevel),
        ];
    }

    /**
     * Check purchase frequency trend.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return array
     */
    protected function checkPurchaseFrequency(Customer $customer, int $businessId): array
    {
        $recentPurchases = Sale::where('business_id', $businessId)
            ->where('party_id', $customer->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->count();

        $previousPurchases = Sale::where('business_id', $businessId)
            ->where('party_id', $customer->id)
            ->whereBetween('created_at', [
                now()->subDays(180),
                now()->subDays(90),
            ])
            ->count();

        if ($previousPurchases > 0 && $recentPurchases < $previousPurchases * 0.5) {
            return [
                'is_risk' => true,
                'risk_score' => 30,
                'type' => 'decreasing_frequency',
                'description' => 'Purchase frequency decreased by 50%',
            ];
        }

        return ['is_risk' => false, 'risk_score' => 0];
    }

    /**
     * Check purchase amount trend.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return array
     */
    protected function checkPurchaseAmount(Customer $customer, int $businessId): array
    {
        $recentAverage = Sale::where('business_id', $businessId)
            ->where('party_id', $customer->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->avg('totalAmount') ?? 0;

        $previousAverage = Sale::where('business_id', $businessId)
            ->where('party_id', $customer->id)
            ->whereBetween('created_at', [
                now()->subDays(180),
                now()->subDays(90),
            ])
            ->avg('totalAmount') ?? 0;

        if ($previousAverage > 0 && $recentAverage < $previousAverage * 0.5) {
            return [
                'is_risk' => true,
                'risk_score' => 25,
                'type' => 'decreasing_amount',
                'description' => 'Average purchase amount decreased by 50%',
            ];
        }

        return ['is_risk' => false, 'risk_score' => 0];
    }

    /**
     * Check purchase recency.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return array
     */
    protected function checkPurchaseRecency(Customer $customer, int $businessId): array
    {
        $lastPurchase = Sale::where('business_id', $businessId)
            ->where('party_id', $customer->id)
            ->latest()
            ->first();

        if (!$lastPurchase) {
            return [
                'is_risk' => true,
                'risk_score' => 40,
                'type' => 'no_recent_purchases',
                'description' => 'No purchases in the last 90 days',
            ];
        }

        $daysSinceLastPurchase = $lastPurchase->created_at->diffInDays(now());

        if ($daysSinceLastPurchase > 90) {
            return [
                'is_risk' => true,
                'risk_score' => 35,
                'type' => 'long_inactive',
                'description' => "Last purchase was {$daysSinceLastPurchase} days ago",
            ];
        }

        return ['is_risk' => false, 'risk_score' => 0];
    }

    /**
     * Check subscription status.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return array
     */
    protected function checkSubscriptionStatus(Customer $customer, int $businessId): array
    {
        $subscription = Subscription::where('business_id', $businessId)
            ->where('customer_id', $customer->id)
            ->first();

        if ($subscription && $subscription->status === 'cancelled') {
            return [
                'is_risk' => true,
                'risk_score' => 50,
                'type' => 'subscription_cancelled',
                'description' => 'Subscription has been cancelled',
            ];
        }

        if ($subscription && $subscription->status === 'pending_cancellation') {
            return [
                'is_risk' => true,
                'risk_score' => 45,
                'type' => 'subscription_pending_cancellation',
                'description' => 'Subscription cancellation pending',
            ];
        }

        return ['is_risk' => false, 'risk_score' => 0];
    }

    /**
     * Check support activity.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return array
     */
    protected function checkSupportActivity(Customer $customer, int $businessId): array
    {
        // Check if customer has opened many support tickets recently
        // This would require a support_tickets table
        // For now, return no risk
        
        return ['is_risk' => false, 'risk_score' => 0];
    }

    /**
     * Determine risk level.
     *
     * @param int $riskScore
     * @return string
     */
    protected function determineRiskLevel(int $riskScore): string
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
     * Get recommended action.
     *
     * @param string $riskLevel
     * @return string
     */
    protected function getRecommendedAction(string $riskLevel): string
    {
        switch ($riskLevel) {
            case 'critical':
                return 'Immediate outreach required - offer discount or incentive';
            case 'high':
                return 'Schedule retention call within 24 hours';
            case 'medium':
                return 'Send re-engagement email with special offer';
            case 'low':
            default:
                return 'Continue normal engagement';
        }
    }

    /**
     * Get at-risk customers for a business.
     *
     * @param int $businessId
     * @param string $riskLevel
     * @return Collection
     */
    public function getAtRiskCustomers(int $businessId, string $riskLevel = 'high'): Collection
    {
        $customers = Customer::where('business_id', $businessId)->get();
        $atRiskCustomers = collect();

        foreach ($customers as $customer) {
            $prediction = $this->predictChurnRisk($customer->id, $businessId);
            
            if ($prediction['risk_level'] === $riskLevel || 
                ($riskLevel === 'high' && in_array($prediction['risk_level'], ['high', 'critical']))) {
                $atRiskCustomers->push([
                    'customer' => $customer,
                    'prediction' => $prediction,
                ]);
            }
        }

        return $atRiskCustomers;
    }
}