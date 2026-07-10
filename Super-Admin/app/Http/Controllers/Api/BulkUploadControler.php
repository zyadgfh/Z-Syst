<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Imports\ProductImport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class BulkUploadControler extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        $businessId = auth()->user()->business_id;

        Excel::import(new ProductImport($businessId), $request->file('file'));

        return response()->json([
            'message' => __('Bulk upload successfully.')
        ]);
    }
}
