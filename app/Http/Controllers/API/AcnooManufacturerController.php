<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manufacturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AcnooManufacturerController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');

        $data = Manufacturer::query()
            ->where('company_id', $companyId)
            ->latest()
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('manufacturers')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $manufacturerPayload = array_merge($data, ['company_id' => $companyId]);
        if (Schema::hasColumn('manufacturers', 'business_id') && Schema::hasTable('businesses')) {
            $hasBusiness = DB::table('businesses')->where('id', $companyId)->exists();
            if ($hasBusiness) {
                $manufacturerPayload['business_id'] = $companyId;
            }
        }

        $manufacturer = Manufacturer::create($manufacturerPayload);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $manufacturer,
        ], 201);
    }

    public function update(Request $request, Manufacturer $manufacturer)
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('manufacturers')->ignore($manufacturer->id)->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $manufacturerPayload = array_merge($data, ['company_id' => $companyId]);
        if (Schema::hasColumn('manufacturers', 'business_id') && Schema::hasTable('businesses')) {
            $hasBusiness = DB::table('businesses')->where('id', $companyId)->exists();
            if ($hasBusiness) {
                $manufacturerPayload['business_id'] = $companyId;
            }
        }

        $manufacturer->update($manufacturerPayload);

        return response()->json([
            'message' => __('Data updated successfully.'),
            'data' => $manufacturer->fresh(),
        ]);
    }

    public function destroy(Manufacturer $manufacturer)
    {
        $manufacturer->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
