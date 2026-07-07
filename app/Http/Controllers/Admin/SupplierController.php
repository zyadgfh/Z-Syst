<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $suppliers = Supplier::forCompany($request->user()->company_id)
            ->when($request->search, fn($q, $v) => $q->where(function($q) use ($v) {
                $q->where('name', 'like', "%{$v}%")
                  ->orWhere('phone', 'like', "%{$v}%")
                  ->orWhere('email', 'like', "%{$v}%")
                  ->orWhere('tax_id', 'like', "%{$v}%");
            }))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->is_active))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return $this->success($suppliers);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'credit_limit' => 'numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $supplier = Supplier::create(array_merge(
            $validator->validated(),
            [
                'company_id' => $request->user()->company_id,
                'created_by' => $request->user()->id,
                'credit_limit' => $request->credit_limit ?? 0,
                'balance' => 0,
            ]
        ));

        return $this->created($supplier, 'Supplier created successfully');
    }

    public function show(Request $request, Supplier $supplier): JsonResponse
    {
        if ($supplier->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        return $this->success($supplier->load(['purchaseOrders', 'goodsReceivedNotes']));
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        if ($supplier->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'credit_limit' => 'numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $supplier->update(array_merge($validator->validated(), ['updated_by' => $request->user()->id]));
        return $this->success($supplier, 'Supplier updated successfully');
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        if ($supplier->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        $supplier->delete();
        return $this->success(null, 'Supplier deleted successfully');
    }
}