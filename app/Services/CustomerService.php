<?php

namespace App\Services;

use App\Models\Party;
use App\Models\Sale;
use App\Models\LoyaltyTransaction;
use App\Models\CustomerInteraction;
use App\Models\Prescription;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CustomerService
{
    use WithTransactionalOperations;

    /**
     * Create a new customer.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return Party
     * @throws \Exception
     */
    public function createCustomer(array $data, int $businessId): Party
    {
        return $this->executeTransaction(function () use ($data, $businessId) {
            $customer = Party::create([
                'business_id' => $businessId,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'type' => 'customer', // Explicitly set as customer
                'due' => 0,
                'meta' => $data['meta'] ?? null,
            ]);

            // Add customer-specific metadata
            $existingMeta = $customer->meta ?? [];
            $customer->update([
                'meta' => array_merge($existingMeta, [
                    'customer_since' => now()->toDateString(),
                    'customer_type' => $data['customer_type'] ?? 'regular',
                    'preferences' => $data['preferences'] ?? [],
                    'notes' => $data['notes'] ?? null,
                ]),
            ]);

            return $customer->fresh();
        });
    }

    /**
     * Update a customer.
     *
     * @param Party $customer
     * @param array<string, mixed> $data
     * @return Party
     * @throws \Exception
     */
    public function updateCustomer(Party $customer, array $data): Party
    {
        return $this->executeTransaction(function () use ($customer, $data) {
            $customer->update([
                'name' => $data['name'] ?? $customer->name,
                'phone' => $data['phone'] ?? $customer->phone,
                'email' => $data['email'] ?? $customer->email,
                'address' => $data['address'] ?? $customer->address,
                'meta' => array_merge($customer->meta ?? [], [
                    'customer_type' => $data['customer_type'] ?? $customer->meta['customer_type'] ?? 'regular',
                    'preferences' => $data['preferences'] ?? $customer->meta['preferences'] ?? [],
                    'notes' => $data['notes'] ?? $customer->meta['notes'] ?? null,
                ]),
            ]);

            return $customer->fresh();
        });
    }

    /**
     * Get customer history.
     *
     * @param int $customerId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getCustomerHistory(int $customerId, array $filters = []): array
    {
        $customer = Party::findOrFail($customerId);

        $salesQuery = Sale::where('party_id', $customerId);
        $prescriptionsQuery = Prescription::where('party_id', $customerId);
        $interactionsQuery = CustomerInteraction::where('party_id', $customerId);

        // Apply date filters
        if (isset($filters['from_date'])) {
            $salesQuery->where('saleDate', '>=', $filters['from_date']);
            $prescriptionsQuery->where('created_at', '>=', $filters['from_date']);
            $interactionsQuery->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $salesQuery->where('saleDate', '<=', $filters['to_date']);
            $prescriptionsQuery->where('created_at', '<=', $filters['to_date']);
            $interactionsQuery->where('created_at', '<=', $filters['to_date']);
        }

        $sales = $salesQuery->with('details.product')->latest()->get();
        $prescriptions = $prescriptionsQuery->with('items.product')->latest()->get();
        $interactions = $interactionsQuery->latest()->get();

        return [
            'customer' => $customer,
            'sales' => [
                'total_count' => $sales->count(),
                'total_amount' => $sales->sum('totalAmount'),
                'total_paid' => $sales->sum('paidAmount'),
                'total_due' => $sales->sum('dueAmount'),
                'recent_sales' => $sales->take(10),
            ],
            'prescriptions' => [
                'total_count' => $prescriptions->count(),
                'active_prescriptions' => $prescriptions->where('status', 'pending')->count(),
                'recent_prescriptions' => $prescriptions->take(10),
            ],
            'interactions' => [
                'total_count' => $interactions->count(),
                'recent_interactions' => $interactions->take(10),
            ],
            'summary' => [
                'total_purchases' => $sales->count(),
                'total_spent' => $sales->sum('totalAmount'),
                'average_purchase_value' => $sales->count() > 0 ? $sales->sum('totalAmount') / $sales->count() : 0,
                'last_purchase_date' => $sales->first()?->saleDate,
                'customer_since' => $customer->meta['customer_since'] ?? $customer->created_at->toDateString(),
            ],
        ];
    }

    /**
     * Get customer loyalty points.
     *
     * @param int $customerId
     * @param int $businessId
     * @return array
     */
    public function getCustomerLoyaltyPoints(int $customerId, int $businessId): array
    {
        $customer = Party::findOrFail($customerId);
        
        $loyaltyTransactions = LoyaltyTransaction::where('party_id', $customerId)
            ->where('business_id', $businessId)
            ->get();

        $totalPoints = $loyaltyTransactions->where('type', 'earned')->sum('points') - 
                      $loyaltyTransactions->where('type', 'redeemed')->sum('points');

        return [
            'customer_id' => $customerId,
            'total_points' => $totalPoints,
            'earned_points' => $loyaltyTransactions->where('type', 'earned')->sum('points'),
            'redeemed_points' => $loyaltyTransactions->where('type', 'redeemed')->sum('points'),
            'transactions' => $loyaltyTransactions->latest()->take(20),
            'available_rewards' => $this->getAvailableRewards($totalPoints, $businessId),
        ];
    }

    /**
     * Segment customers based on criteria.
     *
     * @param int $businessId
     * @param array<string, mixed> $criteria
     * @return Collection
     */
    public function segmentCustomers(int $businessId, array $criteria = []): Collection
    {
        $query = Party::where('business_id', $businessId)
            ->where('type', 'customer');

        // Apply segmentation criteria
        if (isset($criteria['total_spent_min'])) {
            $query->whereHas('sales', function ($q) use ($criteria) {
                $q->havingRaw('SUM(totalAmount) >= ?', [$criteria['total_spent_min']]);
            });
        }

        if (isset($criteria['total_spent_max'])) {
            $query->whereHas('sales', function ($q) use ($criteria) {
                $q->havingRaw('SUM(totalAmount) <= ?', [$criteria['total_spent_max']]);
            });
        }

        if (isset($criteria['purchase_count_min'])) {
            $query->whereHas('sales', function ($q) use ($criteria) {
                $q->havingRaw('COUNT(*) >= ?', [$criteria['purchase_count_min']]);
            });
        }

        if (isset($criteria['last_purchase_days'])) {
            $cutoffDate = now()->subDays($criteria['last_purchase_days']);
            $query->whereHas('sales', function ($q) use ($cutoffDate) {
                $q->where('saleDate', '>=', $cutoffDate);
            });
        }

        if (isset($criteria['customer_type'])) {
            $query->whereJsonContains('meta->customer_type', $criteria['customer_type']);
        }

        if (isset($criteria['has_due'])) {
            $query->where('due', '>', 0);
        }

        if (isset($criteria['loyalty_points_min'])) {
            $query->whereHas('loyaltyTransactions', function ($q) use ($criteria) {
                $q->havingRaw('SUM(CASE WHEN type = "earned" THEN points ELSE -points END) >= ?', [$criteria['loyalty_points_min']]);
            });
        }

        $customers = $query->with(['sales' => function ($q) {
            $q->selectRaw('party_id, SUM(totalAmount) as total_spent, COUNT(*) as purchase_count')
              ->groupBy('party_id');
        }])->get();

        // Add calculated metrics to each customer
        return $customers->map(function ($customer) {
            $totalSpent = $customer->sales->first()?->total_spent ?? 0;
            $purchaseCount = $customer->sales->first()?->purchase_count ?? 0;
            
            return array_merge($customer->toArray(), [
                'calculated_metrics' => [
                    'total_spent' => $totalSpent,
                    'purchase_count' => $purchaseCount,
                    'average_purchase_value' => $purchaseCount > 0 ? $totalSpent / $purchaseCount : 0,
                    'customer_tier' => $this->determineCustomerTier($totalSpent, $purchaseCount),
                ],
            ]);
        });
    }

    /**
     * Add customer interaction.
     *
     * @param int $customerId
     * @param array<string, mixed> $interactionData
     * @param int $businessId
     * @param int $userId
     * @return CustomerInteraction
     */
    public function addCustomerInteraction(int $customerId, array $interactionData, int $businessId, int $userId): CustomerInteraction
    {
        return CustomerInteraction::create([
            'business_id' => $businessId,
            'party_id' => $customerId,
            'type' => $interactionData['type'] ?? 'call',
            'notes' => $interactionData['notes'] ?? null,
            'user_id' => $userId,
        ]);
    }

    /**
     * Get customer analytics.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getCustomerAnalytics(int $businessId, array $filters = []): array
    {
        $query = Party::where('business_id', $businessId)->where('type', 'customer');

        // Apply date filters
        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $customers = $query->get();

        $totalCustomers = $customers->count();
        $activeCustomers = $customers->whereHas('sales', function ($q) {
            $q->where('saleDate', '>=', now()->subDays(90));
        })->count();

        $totalRevenue = Sale::where('business_id', $businessId)
            ->whereIn('party_id', $customers->pluck('id'))
            ->sum('totalAmount');

        $averageRevenuePerCustomer = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'inactive_customers' => $totalCustomers - $activeCustomers,
            'total_revenue' => $totalRevenue,
            'average_revenue_per_customer' => $averageRevenuePerCustomer,
            'customer_segments' => [
                'new' => $customers->where('created_at', '>=', now()->subDays(30))->count(),
                'regular' => $customers->whereBetween('created_at', [now()->subDays(90), now()->subDays(30)])->count(),
                'long_term' => $customers->where('created_at', '<', now()->subDays(90))->count(),
            ],
            'top_customers' => $this->getTopCustomers($businessId, 10),
        ];
    }

    /**
     * Get top customers by revenue.
     *
     * @param int $businessId
     * @param int $limit
     * @return Collection
     */
    protected function getTopCustomers(int $businessId, int $limit = 10): Collection
    {
        return Party::where('business_id', $businessId)
            ->where('type', 'customer')
            ->withCount('sales')
            ->with(['sales' => function ($q) {
                $q->selectRaw('party_id, SUM(totalAmount) as total_spent')
                  ->groupBy('party_id');
            }])
            ->get()
            ->sortByDesc(function ($customer) {
                return $customer->sales->first()?->total_spent ?? 0;
            })
            ->take($limit)
            ->values();
    }

    /**
     * Determine customer tier based on spending and purchase count.
     *
     * @param float $totalSpent
     * @param int $purchaseCount
     * @return string
     */
    protected function determineCustomerTier(float $totalSpent, int $purchaseCount): string
    {
        if ($totalSpent >= 10000 || $purchaseCount >= 50) {
            return 'platinum';
        } elseif ($totalSpent >= 5000 || $purchaseCount >= 25) {
            return 'gold';
        } elseif ($totalSpent >= 2000 || $purchaseCount >= 10) {
            return 'silver';
        } elseif ($totalSpent >= 500 || $purchaseCount >= 5) {
            return 'bronze';
        }
        return 'regular';
    }

    /**
     * Get available rewards for loyalty points.
     *
     * @param int $points
     * @param int $businessId
     * @return array
     */
    protected function getAvailableRewards(int $points, int $businessId): array
    {
        // This would typically come from a loyalty program configuration
        return [
            [
                'points_required' => 100,
                'reward' => '5% discount on next purchase',
                'available' => $points >= 100,
            ],
            [
                'points_required' => 250,
                'reward' => '10% discount on next purchase',
                'available' => $points >= 250,
            ],
            [
                'points_required' => 500,
                'reward' => 'Free product (up to $20 value)',
                'available' => $points >= 500,
            ],
            [
                'points_required' => 1000,
                'reward' => '20% discount on next purchase',
                'available' => $points >= 1000,
            ],
        ];
    }

    /**
     * Merge duplicate customers.
     *
     * @param int $primaryCustomerId
     * @param array<int> $duplicateCustomerIds
     * @param int $businessId
     * @return Party
     * @throws \Exception
     */
    public function mergeCustomers(int $primaryCustomerId, array $duplicateCustomerIds, int $businessId): Party
    {
        return $this->executeTransaction(function () use ($primaryCustomerId, $duplicateCustomerIds, $businessId) {
            $primaryCustomer = Party::findOrFail($primaryCustomerId);
            
            foreach ($duplicateCustomerIds as $duplicateId) {
                $duplicateCustomer = Party::findOrFail($duplicateId);
                
                if ($duplicateCustomer->business_id !== $businessId) {
                    throw new \Exception("Customer {$duplicateId} does not belong to this business");
                }

                // Transfer sales
                Sale::where('party_id', $duplicateId)->update(['party_id' => $primaryCustomerId]);
                
                // Transfer prescriptions
                Prescription::where('party_id', $duplicateId)->update(['party_id' => $primaryCustomerId]);
                
                // Transfer loyalty transactions
                LoyaltyTransaction::where('party_id', $duplicateId)->update(['party_id' => $primaryCustomerId]);
                
                // Transfer customer interactions
                CustomerInteraction::where('party_id', $duplicateId)->update(['party_id' => $primaryCustomerId]);
                
                // Transfer due amount
                $primaryCustomer->increment('due', $duplicateCustomer->due);
                
                // Delete duplicate customer
                $duplicateCustomer->delete();
            }

            return $primaryCustomer->fresh();
        });
    }

    /**
     * Export customer data.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return Collection
     */
    public function exportCustomers(int $businessId, array $filters = []): Collection
    {
        $query = Party::where('business_id', $businessId)->where('type', 'customer');

        // Apply filters
        if (isset($filters['customer_type'])) {
            $query->whereJsonContains('meta->customer_type', $filters['customer_type']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        return $query->with(['sales' => function ($q) {
            $q->selectRaw('party_id, SUM(totalAmount) as total_spent, COUNT(*) as purchase_count')
              ->groupBy('party_id');
        }])->get()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'customer_type' => $customer->meta['customer_type'] ?? 'regular',
                'customer_since' => $customer->meta['customer_since'] ?? $customer->created_at->toDateString(),
                'total_spent' => $customer->sales->first()?->total_spent ?? 0,
                'purchase_count' => $customer->sales->first()?->purchase_count ?? 0,
                'due_amount' => $customer->due,
                'created_at' => $customer->created_at->toDateString(),
            ];
        });
    }
}