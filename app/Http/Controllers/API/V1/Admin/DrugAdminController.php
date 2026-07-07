<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DrugAdminController extends Controller
{
    public function store(Request $request, TenantManager $tenantManager)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:drugs,barcode',
            'manufacturer' => 'nullable|string|max:255',
        ]);

        if (empty($data['barcode'])) {
            $data['barcode'] = Str::slug($data['name']);
        }

        // Tenant binding should set company_id automatically via HasCompany on create
        $drug = Drug::create(array_merge($data, ['uuid' => (string) Str::uuid()]));

        return response()->json($drug, 201);
    }

    public function index(Request $request)
    {
        $query = Drug::query();

        if ($q = $request->query('q')) {
            $query->where('name', 'like', "%{$q}%")->orWhere('generic_name', 'like', "%{$q}%");
        }

        $perPage = (int) $request->query('per_page', 25);
        $data = $query->select('id','uuid','name','generic_name','barcode')->paginate($perPage);
        return response()->json($data);
    }

    public function update(Request $request, Drug $drug)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
        ]);

        $drug->update($data);

        return response()->json($drug);
    }

    public function destroy(Drug $drug)
    {
        $drug->delete();
        return response()->noContent();
    }
}
