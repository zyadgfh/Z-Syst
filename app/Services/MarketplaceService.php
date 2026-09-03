<?php

namespace App\Services;

use App\Models\Business;
use App\Models\AddOn;
use App\Models\BusinessAddOn;
use App\Models\AddOnCategory;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;

class MarketplaceService
{
    use WithTransactionalOperations;

    /**
     * Get available add-ons from marketplace.
     *
     * @param array<string, mixed> $filters
     * @return Collection
     */
    public function getAvailableAddOns(array $filters = []): Collection
    {
        $query = AddOn::where('is_active', true);

        if (isset($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%")
                 ->orWhere('description', 'like', "%{$filters['search']}%");
        }

        return $query->with('category')->get();
    }

    /**
     * Purchase add-on for a business.
     *
     * @param int $businessId
     * @param int $addOnId
     * @return array
     */
    public function purchaseAddOn(int $businessId, int $addOnId): array
    {
        return $this->executeTransaction(function () use ($businessId, $addOnId) {
            $addOn = AddOn::findOrFail($addOnId);

            // Check if already purchased
            $existing = BusinessAddOn::where('business_id', $businessId)
                ->where('add_on_id', $addOnId)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Add-on already purchased',
                ];
            }

            // Create purchase record
            $purchase = BusinessAddOn::create([
                'business_id' => $businessId,
                'add_on_id' => $addOnId,
                'status' => 'active',
                'purchased_at' => now(),
                'expires_at' => $addOn->is_subscription ? now()->addMonth() : null,
            ]);

            return [
                'success' => true,
                'message' => 'Add-on purchased successfully',
                'purchase_id' => $purchase->id,
                'add_on' => $addOn->name,
            ];
        });
    }

    /**
     * Get business add-ons.
     *
     * @param int $businessId
     * @return Collection
     */
    public function getBusinessAddOns(int $businessId): Collection
    {
        return BusinessAddOn::where('business_id', $businessId)
            ->with('addOn')
            ->get()
            ->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'add_on' => $purchase->addOn,
                    'status' => $purchase->status,
                    'purchased_at' => $purchase->purchased_at,
                    'expires_at' => $purchase->expires_at,
                    'is_active' => $purchase->status === 'active' && 
                                   (!$purchase->expires_at || $purchase->expires_at->isFuture()),
                ];
            });
    }

    /**
     * Cancel add-on subscription.
     *
     * @param int $businessId
     * @param int $purchaseId
     * @return array
     */
    public function cancelAddOn(int $businessId, int $purchaseId): array
    {
        $purchase = BusinessAddOn::where('business_id', $businessId)
            ->where('id', $purchaseId)
            ->firstOrFail();

        $purchase->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Add-on cancelled successfully',
        ];
    }

    /**
     * Get add-on categories.
     *
     * @return Collection
     */
    public function getCategories(): Collection
    {
        return AddOnCategory::where('is_active', true)->get();
    }
}