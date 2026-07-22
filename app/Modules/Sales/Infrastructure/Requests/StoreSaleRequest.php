<?php

declare(strict_types=1);

namespace App\Modules\Sales\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for creating a sale.
 *
 * Validates:
 * - Items array with product_id, quantity, unit_price
 * - Customer info (optional)
 * - Payment details
 * - No overselling (validated in service layer)
 */
class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_sales') ?? true;
    }

    public function rules(): array
    {
        return [
            // Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax' => ['nullable', 'numeric', 'min:0'],

            // Customer
            'customer_id' => ['nullable', 'string', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],

            // Branch
            'branch_id' => ['nullable', 'string', 'exists:branches,id'],

            // Payment
            'payment_method' => ['nullable', 'string', 'in:cash,card,wallet,insurance,credit'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],

            // Totals (optional — can be calculated)
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Metadata
            'notes' => ['nullable', 'string', 'max:1000'],
            'prescription_id' => ['nullable', 'string', 'exists:prescriptions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'يجب إضافة منتج واحد على الأقل للفاتورة',
            'items.min' => 'يجب إضافة منتج واحد على الأقل',
            'items.*.product_id.required' => 'معرف المنتج مطلوب',
            'items.*.product_id.exists' => 'المنتج غير موجود',
            'items.*.quantity.required' => 'الكمية مطلوبة',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من 0',
            'items.*.unit_price.required' => 'سعر الوحدة مطلوب',
            'items.*.unit_price.min' => 'سعر الوحدة يجب أن يكون 0 أو أكثر',
            'payment_method.in' => 'طريقة الدفع غير صالحة',
            'customer_id.exists' => 'العميل غير موجود',
            'branch_id.exists' => 'الفرع غير موجود',
            'prescription_id.exists' => 'الوصفة غير موجودة',
        ];
    }
}

