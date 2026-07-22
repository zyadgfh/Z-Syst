<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'tier_name' => 'required|string|max:100',
            'tier_label' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:1',
            'max_quantity' => 'nullable|numeric|min:1',
            'customer_group_id' => 'nullable|uuid|exists:customer_groups,id',
            'is_default' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:500',
        ];
    }
}

