<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderReturnStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_returned' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_order_id.required' => 'يجب تحديد أمر الشراء',
            'purchase_order_id.exists' => 'أمر الشراء غير موجود',
            'supplier_id.required' => 'يجب تحديد المورد',
            'supplier_id.exists' => 'المورد غير موجود',
            'branch_id.required' => 'يجب تحديد الفرع',
            'branch_id.exists' => 'الفرع غير موجود',
            'items.required' => 'يجب إضافة عناصر على الأقل',
            'items.*.quantity_returned.min' => 'كمية الإرجاع يجب أن تكون أكبر من الصفر',
        ];
    }
}
