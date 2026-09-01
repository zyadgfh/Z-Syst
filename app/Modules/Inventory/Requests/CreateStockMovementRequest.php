<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'stock_id' => 'nullable|integer|exists:stocks,id',
            'type' => 'required|string|in:in,out,adjustment,transfer',
            'quantity' => 'required|integer|min:1',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
            'movement_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'يجب تحديد المنتج',
            'product_id.exists' => 'المنتج غير موجود',
            'type.required' => 'يجب تحديد نوع الحركة',
            'type.in' => 'نوع الحركة غير صحيح',
            'quantity.required' => 'يجب تحديد الكمية',
            'quantity.min' => 'الكمية يجب أن تكون أكبر من صفر',
        ];
    }
}
