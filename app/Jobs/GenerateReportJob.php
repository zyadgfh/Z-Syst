<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;
    public $maxExceptions = 2;

    public function __construct(
        public string $reportType,
        public int $businessId,
        public array $filters = [],
        public ?string $format = 'json',
    ) {
        $this->onQueue('reports');
    }

    public function handle(): mixed
    {
        $business = Business::find($this->businessId);

        if (! $business) {
            Log::warning("GenerateReportJob: Business not found: {$this->businessId}");
            return null;
        }

        Log::info("Generating report: {$this->reportType}", [
            'business_id' => $this->businessId,
            'filters' => $this->filters,
            'format' => $this->format,
        ]);

        $service = app(ReportService::class);

        $reportData = match ($this->reportType) {
            'sales' => $service->salesReport($this->businessId, $this->filters),
            'purchases' => $service->purchaseReport($this->businessId, $this->filters),
            'loss-profit' => $service->lossProfitReport($this->businessId, $this->filters),
            'low-stock' => $service->lowStockReport($this->businessId),
            'taxes' => $service->taxesReport($this->businessId, $this->filters),
            default => throw new \InvalidArgumentException("Unknown report type: {$this->reportType}"),
        };

        Log::info("Report generated: {$this->reportType}", [
            'business_id' => $this->businessId,
            'record_count' => is_iterable($reportData) ? count($reportData) : 1,
        ]);

        return $reportData;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateReportJob failed permanently", [
            'report_type' => $this->reportType,
            'business_id' => $this->businessId,
            'error' => $exception->getMessage(),
        ]);
    }
}
