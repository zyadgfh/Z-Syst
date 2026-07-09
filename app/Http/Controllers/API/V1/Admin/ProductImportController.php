<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Drug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductImportController extends Controller
{
    public function importJson(Request $request)
    {
        $data = $request->validate(['rows' => 'required|array']);
        $company = Company::where('slug', 'demo')->first();
        if (! $company) {
            return response()->json(['message' => 'demo company not found'], 404);
        }

        $created = 0;
        foreach ($data['rows'] as $row) {
            $validator = Validator::make($row, [
                'name' => 'required|string',
                'generic_name' => 'nullable|string',
                'barcode' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                continue;
            }

            $payload = $validator->validated();
            if (empty($payload['barcode'])) {
                $payload['barcode'] = Str::slug($payload['name']);
            }

            $drug = Drug::firstOrCreate(['company_id' => $company->id, 'barcode' => $payload['barcode']], array_merge($payload, ['uuid' => (string) Str::uuid()]));
            if ($drug->wasRecentlyCreated) {
                $created++;
            }
        }

        return response()->json(['created' => $created]);
    }
}
