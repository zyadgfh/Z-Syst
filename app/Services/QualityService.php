<?php

namespace App\Services;

use App\Models\QualityCheck;
use App\Models\QualityReport;
use App\Models\QualityStandard;

class QualityService
{
    public function createStandard(array $data): QualityStandard
    {
        return QualityStandard::create($data);
    }

    public function generateReport(int $businessId, ?int $branchId = null): QualityReport
    {
        $checks = QualityCheck::whereHas('grnItem.grn', function ($query) use ($businessId, $branchId) {
            $query->where('business_id', $businessId);
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        })->get();

        return QualityReport::create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'report_date' => now(),
            'total_checks' => $checks->count(),
            'passed' => $checks->where('quality_status', 'passed')->count(),
            'failed' => $checks->where('quality_status', 'failed')->count(),
            'pass_rate' => $checks->count() > 0
                ? ($checks->where('quality_status', 'passed')->count() / $checks->count()) * 100
                : 0,
            'generated_by' => auth()->id(),
        ]);
    }

    public function getStandards(int $businessId)
    {
        return QualityStandard::where('business_id', $businessId)
            ->active()
            ->with('category')
            ->get();
    }

    public function getReports(int $businessId)
    {
        return QualityReport::forBusiness($businessId)
            ->with(['supplier', 'branch', 'generatedBy'])
            ->latest()
            ->get();
    }
}
