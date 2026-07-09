<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\API\BaseController;
use App\Models\Manufacturer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ManufacturerController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $manufacturers = Manufacturer::forCompany($request->user()->company_id)
            ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($request->is_active !== null, fn ($q) => $q->where('is_active', $request->is_active))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return $this->success($manufacturers);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $manufacturer = Manufacturer::create(array_merge(
            $validator->validated(),
            ['company_id' => $request->user()->company_id, 'created_by' => $request->user()->id]
        ));

        return $this->created($manufacturer, 'Manufacturer created successfully');
    }

    public function show(Request $request, Manufacturer $manufacturer): JsonResponse
    {
        if ($manufacturer->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        return $this->success($manufacturer->load('products'));
    }

    public function update(Request $request, Manufacturer $manufacturer): JsonResponse
    {
        if ($manufacturer->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $manufacturer->update(array_merge(
            $validator->validated(),
            ['updated_by' => $request->user()->id]
        ));

        return $this->success($manufacturer, 'Manufacturer updated successfully');
    }

    public function destroy(Request $request, Manufacturer $manufacturer): JsonResponse
    {
        if ($manufacturer->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        $manufacturer->delete();

        return $this->success(null, 'Manufacturer deleted successfully');
    }
}
