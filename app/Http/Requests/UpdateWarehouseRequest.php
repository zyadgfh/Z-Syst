<?php

namespace App\Http\Requests;

class UpdateWarehouseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', \App\Models\Warehouse::findOrFail($this->route('warehouse')));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
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
        ];
    }
}
