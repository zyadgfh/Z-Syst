<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Product Search Service
 * 
 * Provides advanced search capabilities including:
 * - Fuzzy search
 * - Phonetic search
 * - Barcode scanning
 * - Multi-field search
 */
class ProductSearchService
{
    /**
     * Perform fuzzy search on products.
     */
    public function fuzzySearch(string $query, int $limit = 20): LengthAwarePaginator
    {
        $searchTerm = trim($query);
        
        // Clean and prepare search term
        $searchTerm = $this->normalizeSearchTerm($searchTerm);
        
        $products = Product::query()
            ->select([
                'id', 
                'generic_name', 
                'brand_name', 
                'product_name',
                'barcode', 
                'product_code',
                'strength', 
                'dosage_form',
                'sales_price',
            ])
            ->withSum('inventory', 'quantity')
            ->where(function ($q) use ($searchTerm) {
                // Exact matches (highest priority)
                $q->where('barcode', $searchTerm)
                    ->orWhere('product_code', $searchTerm);
                
                // ILIKE matches (PostgreSQL case-insensitive)
                $q->orWhere('generic_name', 'ilike', "%{$searchTerm}%")
                    ->orWhere('brand_name', 'ilike', "%{$searchTerm}%")
                    ->orWhere('product_name', 'ilike', "%{$searchTerm}%");
                
                // Fuzzy matches using similarity (PostgreSQL)
                $q->orWhereRaw("similarity(generic_name, ?) > 0.5", [$searchTerm])
                    ->orWhereRaw("similarity(brand_name, ?) > 0.5", [$searchTerm]);
            })
            ->orderByRaw("
                CASE 
                    WHEN barcode = ? THEN 1
                    WHEN product_code = ? THEN 2
                    WHEN generic_name ILIKE ? THEN 3
                    ELSE 4
                END
            ", [$searchTerm, $searchTerm, "{$searchTerm}%"])
            ->paginate($limit);

        return $products;
    }

    /**
     * Search by barcode (fast exact match).
     */
    public function searchByBarcode(string $barcode): ?Product
    {
        return Product::where('barcode', $barcode)
            ->with([
                'category', 
                'manufacturer', 
                'variants',
                'inventory' => function ($q) {
                    $q->where('quantity', '>', 0)
                        ->orderBy('expiry_date', 'asc');
                },
            ])
            ->first();
    }

    /**
     * Search by multiple fields.
     */
    public function searchByMultipleFields(array $criteria): LengthAwarePaginator
    {
        $query = Product::with(['category', 'manufacturer', 'variants']);
        
        foreach ($criteria as $field => $value) {
            if ($value === null) continue;
            
            match($field) {
                'generic_name' => $query->where('generic_name', 'ilike', "%{$value}%"),
                'brand_name' => $query->where('brand_name', 'ilike', "%{$value}%"),
                'dosage_form' => $query->where('dosage_form', $value),
                'strength' => $query->where('strength', 'ilike', "%{$value}%"),
                'manufacturer' => $query->whereHas('manufacturer', fn($q) => $q->where('name', 'ilike', "%{$value}%")),
                'category' => $query->whereHas('category', fn($q) => $q->where('name', 'ilike', "%{$value}%")),
                default => null,
            };
        }
        
        return $query->paginate(15);
    }

    /**
     * Get product suggestions for autocomplete.
     */
    public function getSuggestions(string $query): array
    {
        $searchTerm = trim($query);
        
        if (strlen($searchTerm) < 2) {
            return [];
        }
        
        return Product::select([
                'id',
                'generic_name',
                'brand_name',
                'product_name',
                'barcode',
                'sales_price',
            ])
            ->where(function ($q) use ($searchTerm) {
                $q->where('generic_name', 'ilike', "{$searchTerm}%")
                    ->orWhere('brand_name', 'ilike', "{$searchTerm}%")
                    ->orWhere('product_name', 'ilike', "{$searchTerm}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'label' => $product->generic_name ?? $product->brand_name ?? $product->product_name,
                    'sublabel' => $product->brand_name ?? null,
                    'value' => $product->generic_name ?? $product->brand_name ?? $product->product_name,
                    'price' => $product->sales_price,
                ];
            })
            ->toArray();
    }

    /**
     * Phonetic search (for Arabic/English variants).
     */
    public function phoneticSearch(string $query): LengthAwarePaginator
    {
        $normalized = $this->normalizeSearchTerm($query);
        
        return Product::query()
            ->where(function ($q) use ($normalized) {
                $q->where('generic_name', 'ilike', "%{$normalized}%")
                    ->orWhere('brand_name', 'ilike', "%{$normalized}%");
            })
            ->paginate(15);
    }

    /**
     * Normalize search term by removing special characters.
     */
    protected function normalizeSearchTerm(string $term): string
    {
        // Remove special characters and extra spaces
        $term = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $term);
        $term = preg_replace('/\s+/', ' ', $term);
        
        return trim($term);
    }
}