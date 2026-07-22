<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Discount;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DiscountService
{
    /**
     * List discounts with filters.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Discount::query()
            ->with(['products:id,name,product_name', 'categories:id,name']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create discount.
     */
    public function create(array $data): Discount
    {
        return DB::transaction(function () use ($data) {
            $discount = Discount::create([
                'company_id' => $data['company_id'] ?? app('tenant.company_id'),
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'type' => $data['type'] ?? 'percentage',
                'value' => $data['value'],
                'min_purchase_amount' => $data['min_purchase_amount'] ?? null,
                'max_discount_amount' => $data['max_discount_amount'] ?? null,
                'apply_to' => $data['apply_to'] ?? 'all',
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'usage_limit' => $data['usage_limit'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Attach specific products
            if (!empty($data['product_ids'])) {
                $discount->products()->attach($data['product_ids']);
            }

            // Attach specific categories
            if (!empty($data['category_ids'])) {
                $discount->categories()->attach($data['category_ids']);
            }

            return $discount->fresh(['products', 'categories']);
        });
    }

    /**
     * Update discount.
     */
    public function update(Discount $discount, array $data): Discount
    {
        return DB::transaction(function () use ($discount, $data) {
            $discount->update([
                'name' => $data['name'] ?? $discount->name,
                'code' => $data['code'] ?? $discount->code,
                'type' => $data['type'] ?? $discount->type,
                'value' => $data['value'] ?? $discount->value,
                'min_purchase_amount' => $data['min_purchase_amount'] ?? $discount->min_purchase_amount,
                'max_discount_amount' => $data['max_discount_amount'] ?? $discount->max_discount_amount,
                'apply_to' => $data['apply_to'] ?? $discount->apply_to,
                'start_date' => $data['start_date'] ?? $discount->start_date,
                'end_date' => $data['end_date'] ?? $discount->end_date,
                'start_time' => $data['start_time'] ?? $discount->start_time,
                'end_time' => $data['end_time'] ?? $discount->end_time,
                'usage_limit' => $data['usage_limit'] ?? $discount->usage_limit,
                'is_active' => $data['is_active'] ?? $discount->is_active,
                'description' => $data['description'] ?? $discount->description,
                'updated_by' => Auth::id(),
            ]);

            // Sync products/categories if provided
            if (isset($data['product_ids'])) {
                $discount->products()->sync($data['product_ids']);
            }
            if (isset($data['category_ids'])) {
                $discount->categories()->sync($data['category_ids']);
            }

            return $discount->fresh(['products', 'categories']);
        });
    }

    /**
     * Delete discount.
     */
    public function delete(Discount $discount): bool
    {
        return DB::transaction(function () use ($discount) {
            $discount->products()->detach();
            $discount->categories()->detach();
            return $discount->delete();
        });
    }

    /**
     * Calculate applicable discount for a product.
     */
    public function calculateProductDiscount(Product $product, float $price, int $quantity = 1): array
    {
        $applicableDiscounts = [];

        // Get all active discounts
        $discounts = Discount::active()
            ->where('company_id', $product->company_id)
            ->get();

        foreach ($discounts as $discount) {
            $apply = false;

            switch ($discount->apply_to) {
                case 'all':
                    $apply = true;
                    break;
                case 'products':
                    $apply = $discount->products()->where('product_id', $product->id)->exists();
                    break;
                case 'categories':
                    $apply = $product->category_id && $discount->categories()->where('category_id', $product->category_id)->exists();
                    break;
                case 'specific_products':
                    $apply = $discount->products()->where('product_id', $product->id)->exists();
                    break;
            }

            if ($apply) {
                $discountAmount = $discount->calculateDiscount($price, $quantity);
                if ($discountAmount > 0) {
                    $applicableDiscounts[] = [
                        'discount' => $discount,
                        'amount' => $discountAmount,
                        'final_price' => ($price * $quantity) - $discountAmount,
                    ];
                }
            }
        }

        return $applicableDiscounts;
    }
}

