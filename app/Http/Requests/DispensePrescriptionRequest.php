<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DispensePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required_without:items.*.barcode', 'exists:prescription_items,id'],
            'items.*.barcode' => ['required_without:items.*.id', 'string'],
            'items.*.dispensed_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Ensure at least one of id or barcode is provided
        $items = $this->input('items', []);
        foreach ($items as $index => $item) {
            if (empty($item['id']) && empty($item['barcode'])) {
                $this->validator->errors()->add(
                    "items.{$index}",
                    'Either id or barcode must be provided.'
                );
            }
        }
    }
}