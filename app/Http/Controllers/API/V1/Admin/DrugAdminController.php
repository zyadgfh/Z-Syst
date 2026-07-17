<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DrugResource;
use App\Models\Drug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DrugAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Drug::query();

        if ($q = $request->query('q')) {
            $query->where('name', 'like', "%{$q}%")
                ->orWhere('generic_name', 'like', "%{$q}%")
                ->orWhere('barcode', 'like', "%{$q}%");
        }

        $perPage = (int) $request->query('per_page', 25);
        $data = $query->select('id', 'uuid', 'name', 'generic_name', 'barcode')->paginate($perPage);

        return DrugResource::collection($data)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                'unique:drugs,barcode,NULL,id,company_id,' . ($request->user()->company_id ?? app('tenant.company_id')),
            ],
            'manufacturer' => 'nullable|string|max:255',
        ]);

        if (empty($data['barcode'])) {
            $data['barcode'] = Str::slug($data['name']);
        }

        $companyId = $request->user()->company_id ?? app('tenant.company_id');
        $drug = Drug::create(array_merge($data, [
            'uuid' => (string) Str::uuid(),
            'company_id' => $companyId,
        ]));

        return (new DrugResource($drug))->response()->setStatusCode(201);
    }

    public function update(Request $request, Drug $drug): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                'unique:drugs,barcode,' . $drug->id . ',id,company_id,' . ($request->user()->company_id ?? app('tenant.company_id')),
            ],
            'manufacturer' => 'nullable|string|max:255',
        ]);

        $drug->update($data);

        return (new DrugResource($drug))->response();
    }

    public function destroy(Drug $drug): JsonResponse
    {
        $drug->delete();

        return response()->json(['success' => true, 'message' => 'Drug deleted successfully.']);
    }
}
