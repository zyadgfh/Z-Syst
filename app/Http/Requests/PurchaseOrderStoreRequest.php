<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'يجب تحديد المورد',
            'supplier_id.exists' => 'المورد غير موجود',
            'branch_id.required' => 'يجب تحديد الفرع',
            'branch_id.exists' => 'الفرع غير موجود',
            'items.required' => 'يجب إضافة عناصر على الأقل',
            'items.*.product_id.required' => 'يجب تحديد المنتج',
            'items.*.quantity_ordered.min' => 'الكمية يجب أن تكون أكبر من الصفر',
            'items.*.unit_cost.min' => 'سعر الوحدة يجب أن يكون صفر أو أكثر',
        ];
    }
}
