<?php

namespace App\Services\AI;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use Illuminate\Support\Collection;

class RecommendationEngine
{
    /**
     * Get product recommendations for a customer.
     *
     * @param int $customerId
     * @param int $businessId
     * @param int $limit
     * @return array
     */
    public function getRecommendations(int $customerId, int $businessId, int $limit = 10): array
    {
        $customer = Customer::findOrFail($customerId);
        
        $recommendations = [];

        // Strategy 1: Collaborative filtering (based on similar customers)
        $collaborative = $this->getCollaborativeRecommendations($customer, $businessId, $limit / 2);
        $recommendations = array_merge($recommendations, $collaborative);

        // Strategy 2: Content-based filtering (based on purchase history)
        $contentBased = $this->getContentBasedRecommendations($customer, $businessId, $limit / 2);
        $recommendations = array_merge($recommendations, $contentBased);

        // Strategy 3: Popular products
        $popular = $this->getPopularProducts($businessId, $limit / 4);
        $recommendations = array_merge($recommendations, $popular);

        // Remove duplicates and limit
        $uniqueRecommendations = $this->removeDuplicates($recommendations);
        $finalRecommendations = array_slice($uniqueRecommendations, 0, $limit);

        return [
            'customer_id' => $customerId,
            'recommendations' => $finalRecommendations,
            'total' => count($finalRecommendations),
        ];
    }

    /**
     * Get collaborative filtering recommendations.
     *
     * @param Customer $customer
     * @param int $businessId
     * @param int $limit
     * @return array
     */
    protected function getCollaborativeRecommendations(Customer $customer, int $businessId, int $limit): array
    {
        // Find customers with similar purchase patterns
        $customerPurchases = SaleDetails::whereHas('sale', function ($query) use ($businessId, $customer) {
            $query->where('business_id', $businessId)
                  ->where('party_id', $customer->id);
        })->pluck('product_id')->toArray();

        if (empty($customerPurchases)) {
            return [];
        }

        // Find other customers who bought similar products
        $similarCustomers = SaleDetails::whereIn('product_id', $customerPurchases)
            ->whereHas('sale', function ($query) use ($businessId, $customer) {
                $query->where('business_id', $businessId)
                      ->where('party_id', '!=', $customer->id);
            })
            ->distinct('sale.party_id')
            ->pluck('sale.party_id')
            ->take(20);

        // Get products purchased by similar customers
        $recommendedProducts = SaleDetails::whereHas('sale', function ($query) use ($businessId, $similarCustomers) {
            $query->where('business_id', $businessId)
                  ->whereIn('party_id', $similarCustomers);
        })
        ->whereNotIn('product_id', $customerPurchases)
        ->selectRaw('product_id, COUNT(*) as purchase_count')
        ->groupBy('product_id')
        ->orderByDesc('purchase_count')
        ->limit($limit)
        ->get();

        return $recommendedProducts->map(function ($item) {
            $product = Product::find($item->product_id);
            return [
                'product_id' => $item->product_id,
                'product_name' => $product ? $product->productName : 'Unknown',
                'reason' => 'Customers with similar preferences bought this',
                'confidence' => min(1, $item->purchase_count / 10),
            ];
        })->toArray();
    }

    /**
     * Get content-based recommendations.
     *
     * @param Customer $customer
     * @param int $businessId
     * @param int $limit
     * @return array
     */
    protected function getContentBasedRecommendations(Customer $customer, int $businessId, int $limit): array
    {
        // Get customer's purchased products
        $purchasedProducts = SaleDetails::whereHas('sale', function ($query) use ($businessId, $customer) {
            $query->where('business_id', $businessId)
                  ->where('party_id', $customer->id);
        })->with('product')->get();

        if ($purchasedProducts->isEmpty()) {
            return [];
        }

        // Find products in same categories
        $categories = $purchasedProducts->pluck('product.category_id')->unique()->filter();

        if ($categories->isEmpty()) {
            return [];
        }

        $purchasedIds = $purchasedProducts->pluck('product_id')->toArray();

        $recommendedProducts = Product::where('business_id', $businessId)
            ->whereIn('category_id', $categories)
            ->whereNotIn('id', $purchasedIds)
            ->where('isActive', true)
            ->limit($limit)
            ->get();

        return $recommendedProducts->map(function ($product) {
            return [
                'product_id' => $product->id,
                'product_name' => $product->productName,
                'reason' => 'Similar to products you purchased',
                'confidence' => 0.7,
            ];
        })->toArray();
    }

    /**
     * Get popular products.
     *
     * @param int $businessId
     * @param int $limit
     * @return array
     */
    protected function getPopularProducts(int $businessId, int $limit): array
    {
        $popularProducts = SaleDetails::whereHas('sale', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
        })
        ->selectRaw('product_id, COUNT(*) as purchase_count')
        ->groupBy('product_id')
        ->orderByDesc('purchase_count')
        ->limit($limit)
        ->get();

        return $popularProducts->map(function ($item) {
            $product = Product::find($item->product_id);
            return [
                'product_id' => $item->product_id,
                'product_name' => $product ? $product->productName : 'Unknown',
                'reason' => 'Popular with other customers',
                'confidence' => min(1, $item->purchase_count / 50),
            ];
        })->toArray();
    }

    /**
     * Remove duplicate recommendations.
     *
     * @param array $recommendations
     * @return array
     */
    protected function removeDuplicates(array $recommendations): array
    {
        $seen = [];
        $unique = [];

        foreach ($recommendations as $rec) {
            $key = $rec['product_id'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $rec;
            }
        }

        return $unique;
    }

    /**
     * Get frequently bought together products.
     *
     * @param int $productId
     * @param int $businessId
     * @param int $limit
     * @return array
     */
    public function getFrequentlyBoughtTogether(int $productId, int $businessId, int $limit = 5): array
    {
        // Find sales that include this product
        $salesWithProduct = Sale::where('business_id', $businessId)
            ->whereHas('details', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->pluck('id');

        if ($salesWithProduct->isEmpty()) {
            return [];
        }

        // Find other products in those sales
        $otherProducts = SaleDetails::whereIn('sale_id', $salesWithProduct)
            ->where('product_id', '!=', $productId)
            ->selectRaw('product_id, COUNT(*) as co_occurrence')
            ->groupBy('product_id')
            ->orderByDesc('co_occurrence')
            ->limit($limit)
            ->get();

        return $otherProducts->map(function ($item) {
            $product = Product::find($item->product_id);
            return [
                'product_id' => $item->product_id,
                'product_name' => $product ? $product->productName : 'Unknown',
                'co_occurrence_count' => $item->co_occurrence,
            ];
        })->toArray();
    }
}