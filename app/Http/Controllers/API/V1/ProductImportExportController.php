<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Exports\ProductExport;
use App\Http\Controllers\Controller;
use App\Imports\ProductImport;
use App\Models\ProductImport as ProductImportLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImportExportController extends Controller
{
    /**
     * Export products to Excel.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $export = new ProductExport(
            companyId: $request->user()->company_id ?? app('tenant.company_id'),
            categoryId: $request->category_id,
            search: $request->search,
            selectedIds: $request->ids ?? [],
        );

        $fileName = 'products-export-' . now()->format('YmdHis') . '.xlsx';

        return Excel::download($export, $fileName);
    }

    /**
     * Import products from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        $companyId = $request->user()->company_id ?? app('tenant.company_id');
        $file = $request->file('file');
        $path = $file->store('imports', 'local');

        // Create import log
        $importLog = ProductImportLog::create([
            'company_id' => $companyId,
            'imported_by' => $request->user()->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => 'processing',
            'total_rows' => 0,
            'imported_rows' => 0,
            'failed_rows' => 0,
        ]);

        try {
            $import = new ProductImport($companyId, $importLog->id, $request->user()->id);
            Excel::import($import, $path, 'local');

            $importLog->refresh();

            return response()->json([
                'success' => true,
                'message' => __('Products imported successfully.'),
                'data' => [
                    'import_id' => $importLog->id,
                    'imported_rows' => $importLog->imported_rows,
                    'failed_rows' => $importLog->failed_rows,
                    'total_rows' => $importLog->total_rows,
                    'errors' => $importLog->errors,
                ],
            ]);
        } catch (\Exception $e) {
            $importLog->update([
                'status' => 'failed',
                'errors' => [['error' => $e->getMessage()]],
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Import failed: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Download import template.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $export = new ProductExport();

        return Excel::download($export, 'products-import-template.xlsx');
    }

    /**
     * Get import history.
     */
    public function importHistory(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');

        $imports = ProductImportLog::where('company_id', $companyId)
            ->with('importer:id,name,email')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $imports,
        ]);
    }
}

