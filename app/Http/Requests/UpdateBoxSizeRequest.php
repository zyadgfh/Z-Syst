<?php

namespace App\Http\Requests;

class UpdateBoxSizeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:box_sizes,name,' . $this->route('boxSize')->id . ',id,business_id,' . auth()->user()->business_id,
        ];
    }
}
