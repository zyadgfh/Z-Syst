<?php

namespace App\Http\Requests;

class StoreWarehouseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Warehouse::class);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:warehouses,code,NULL,id,business_id,' . auth()->user()?->business_id,
            'location' => 'nullable|string|max:255',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'capacity' => 'nullable|integer|min:0',
            'manager_id' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Warehouse name is required'),
            'code.unique' => __('Warehouse code already exists'),
        ];
    }
}
