<?php

namespace App\Http\Requests;

class StoreBrandRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Banner::class);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'required|image|max:2048',
            'link' => 'nullable|url|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('Banner title is required'),
            'image.required' => __('Banner image is required'),
        ];
    }
}
