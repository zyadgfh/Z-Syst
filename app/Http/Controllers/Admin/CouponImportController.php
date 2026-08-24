<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponImportController extends Controller
{
    public function index()
    {
        return view('admin.coupons.import');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $rows = $this->parseCsv($file);

        if (empty($rows)) {
            return back()->with('error', 'الملف فارغ أو التنسيق غير صحيح');
        }

        // Validate each row
        $validated = [];
        $errors = [];
        $businessId = auth()->user()->business_id;

        foreach ($rows as $idx => $row) {
            $lineNum = $idx + 1;
            $rowErrors = [];

            $code = strtoupper(trim($row['code'] ?? ''));
            if (empty($code)) {
                $rowErrors[] = 'الكود مطلوب';
            } elseif (strlen($code) > 50) {
                $rowErrors[] = 'الكود طويل جداً';
            }

            $type = strtolower(trim($row['type'] ?? 'percentage'));
            if (!in_array($type, ['percentage', 'fixed'])) {
                $rowErrors[] = 'النوع يجب أن يكون percentage أو fixed';
            }

            $value = (float) ($row['value'] ?? 0);
            if ($value <= 0) {
                $rowErrors[] = 'القيمة يجب أن تكون أكبر من 0';
            }

            if (!empty($rowErrors)) {
                $errors[$lineNum] = $rowErrors;
                continue;
            }

            $validated[] = [
                'line'                    => $lineNum,
                'code'                    => $code,
                'description'             => trim($row['description'] ?? ''),
                'type'                    => $type,
                'value'                   => $value,
                'minimum_order_amount'    => max(0, (float) ($row['min_order'] ?? 0)),
                'maximum_discount_amount' => isset($row['max_discount']) ? (float) $row['max_discount'] : null,
                'usage_limit'             => isset($row['usage_limit']) ? (int) $row['usage_limit'] : null,
                'usage_limit_per_user'    => isset($row['per_user_limit']) ? (int) $row['per_user_limit'] : 1,
                'starts_at'               => !empty($row['starts_at']) ? $row['starts_at'] : null,
                'expires_at'              => !empty($row['expires_at']) ? $row['expires_at'] : null,
                'active'                  => strtolower(trim($row['active'] ?? 'true')) !== 'false',
                'business_id'             => $businessId,
            ];
        }

        // Store in session for confirm step
        session(['coupon_import_data' => $validated, 'coupon_import_errors' => $errors]);

        return view('admin.coupons.import-preview', compact('validated', 'errors'));
    }

    public function confirm()
    {
        $data = session('coupon_import_data', []);

        if (empty($data)) {
            return redirect()->route('admin.coupons.import')
                ->with('error', 'لا توجد بيانات للتأكيد. يرجى رفع الملف مرة أخرى.');
        }

        $created = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($data as $row) {
                unset($row['line']);

                // Check for duplicate code
                $exists = Coupon::where('code', $row['code'])
                    ->where('business_id', $row['business_id'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                Coupon::create($row);
                $created++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء الاستيراد: ' . $e->getMessage());
        }

        session()->forget(['coupon_import_data', 'coupon_import_errors']);

        return redirect()->route('admin.coupons.index')
            ->with('success', "تم الاستيراد بنجاح: {$created} كوبون جديد" . ($skipped > 0 ? " — تم تخطي {$skipped} كوبون مكرر" : ''));
    }

    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="coupon-import-sample.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            // Header row
            fputcsv($handle, ['code', 'type', 'value', 'description', 'min_order', 'max_discount', 'usage_limit', 'per_user_limit', 'starts_at', 'expires_at', 'active']);
            // Sample rows
            fputcsv($handle, ['SUMMER20', 'percentage', '20', 'Summer discount', '50', '30', '100', '1', '', '2026-12-31', 'true']);
            fputcsv($handle, ['FLAT10', 'fixed', '10', 'Fixed $10 off', '25', '', '50', '2', '', '', 'true']);
            fputcsv($handle, ['VIP50', 'percentage', '50', 'VIP exclusive', '100', '50', '1', '1', '2026-09-01', '2026-12-31', 'true']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function parseCsv($file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) return [];

        $header = fgetcsv($handle);
        if (!$header) return [];

        // Normalize headers
        $header = array_map(fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', $h))), $header);

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) === count($header)) {
                $rows[] = array_combine($header, $line);
            }
        }
        fclose($handle);

        return $rows;
    }
}
