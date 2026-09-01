<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerHealthScore;
use App\Models\CustomerSuccessTask;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CustomerSuccessService
{
    use WithTransactionalOperations;

    /**
     * Calculate customer health score.
     *
     * @param int $customerId
     * @param int $businessId
     * @return array
     */
    public function calculateHealthScore(int $customerId, int $businessId): array
    {
        $customer = Customer::findOrFail($customerId);
        
        $score = 100;
        $factors = [];

        // Factor 1: Purchase frequency (30 points)
        $frequencyScore = $this->calculateFrequencyScore($customer, $businessId);
        $factors['frequency'] = $frequencyScore;
        $score += $frequencyScore - 30;

        // Factor 2: Purchase amount (25 points)
        $amountScore = $this->calculateAmountScore($customer, $businessId);
        $factors['amount'] = $amountScore;
        $score += $amountScore - 25;

        // Factor 3: Engagement (20 points)
        $engagementScore = $this->calculateEngagementScore($customer, $businessId);
        $factors['engagement'] = $engagementScore;
        $score += $engagementScore - 20;

        // Factor 4: Support interactions (15 points)
        $supportScore = $this->calculateSupportScore($customer, $businessId);
        $factors['support'] = $supportScore;
        $score += $supportScore - 15;

        // Factor 5: Subscription status (10 points)
        $subscriptionScore = $this->calculateSubscriptionScore($customer, $businessId);
        $factors['subscription'] = $subscriptionScore;
        $score += $subscriptionScore - 10;

        $healthLevel = $this->determineHealthLevel($score);

        // Save health score
        CustomerHealthScore::updateOrCreate(
            ['customer_id' => $customerId],
            [
                'business_id' => $businessId,
                'score' => max(0, min(100, $score)),
                'health_level' => $healthLevel,
                'factors' => $factors,
                'calculated_at' => now(),
            ]
        );

        return [
            'customer_id' => $customerId,
            'score' => max(0, min(100, $score)),
            'health_level' => $healthLevel,
            'factors' => $factors,
        ];
    }

    /**
     * Calculate frequency score.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return int
     */
    protected function calculateFrequencyScore(Customer $customer, int $businessId): int
    {
        $purchaseCount = Sale::where('business_id', $businessId)
            ->where('party_id', $customer->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->count();

        if ($purchaseCount >= 10) return 30;
        if ($purchaseCount >= 5) return 24;
        if ($purchaseCount >= 3) return 18;
        if ($purchaseCount >= 1) return 12;
        return 6;
    }

    /**
     * Calculate amount score.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return int
     */
    protected function calculateAmountScore(Customer $customer, int $businessId): int
    {
        $totalAmount = Sale::where('business_id', $businessId)
            ->where('party_id', $customer->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->sum('totalAmount');

        if ($totalAmount >= 5000) return 25;
        if ($totalAmount >= 2500) return 20;
        if ($totalAmount >= 1000) return 15;
        if ($totalAmount >= 500) return 10;
        return 5;
    }

    /**
     * Calculate engagement score.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return int
     */
    protected function calculateEngagementScore(Customer $customer, int $businessId): int
    {
        // This would track email opens, app usage, etc.
        // For now, return a default score
        
        return 15;
    }

    /**
     * Calculate support score.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return int
     */
    protected function calculateSupportScore(Customer $customer, int $businessId): int
    {
        // Fewer support tickets = higher score
        // This would require a support_tickets table
        
        return 12;
    }

    /**
     * Calculate subscription score.
     *
     * @param Customer $customer
     * @param int $businessId
     * @return int
     */
    protected function calculateSubscriptionScore(Customer $customer, int $businessId): int
    {
        $subscription = Subscription::where('business_id', $businessId)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$subscription) return 5;
        if ($subscription->status === 'active') return 10;
        if ($subscription->status === 'trial') return 8;
        return 3;
    }

    /**
     * Determine health level.
     *
     * @param int $score
     * @return string
     */
    protected function determineHealthLevel(int $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        return 'poor';
    }

    /**
     * Create customer success task.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return CustomerSuccessTask
     */
    public function createTask(array $data, int $businessId): CustomerSuccessTask
    {
        return CustomerSuccessTask::create([
            'business_id' => $businessId,
            'customer_id' => $data['customer_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'outreach',
            'priority' => $data['priority'] ?? 'medium',
            'due_date' => $data['due_date'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * Get customer success dashboard.
     *
     * @param int $businessId
     * @return array
     */
    public function getDashboard(int $businessId): array
    {
        $customers = Customer::where('business_id', $businessId)->get();
        
        $healthScores = CustomerHealthScore::where('business_id', $businessId)->get();
        
        $excellent = $healthScores->where('health_level', 'excellent')->count();
        $good = $healthScores->where('health_level', 'good')->count();
        $fair = $healthScores->where('health_level', 'fair')->count();
        $poor = $healthScores->where('health_level', 'poor')->count();

        $pendingTasks = CustomerSuccessTask::where('business_id', $businessId)
            ->where('status', 'pending')
            ->count();

        return [
            'total_customers' => $customers->count(),
            'health_distribution' => [
                'excellent' => $excellent,
                'good' => $good,
                'fair' => $fair,
                'poor' => $poor,
            ],
            'average_health_score' => $healthScores->avg('score') ?? 0,
            'pending_tasks' => $pendingTasks,
            'at_risk_customers' => $fair + $poor,
        ];
    }
}