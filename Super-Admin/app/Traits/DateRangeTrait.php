<?php

namespace App\Traits;

use Carbon\Carbon;

trait DateRangeTrait
{
    public function applyDateRange(?string $duration, $fromDate = null, $toDate = null): array
    {
        return match ($duration) {
            'today' => [Carbon::today(), Carbon::today()],
            'yesterday' => [Carbon::yesterday(), Carbon::yesterday()],
            'last_seven_days' => [Carbon::today()->subDays(6), Carbon::today()],
            'last_thirty_days' => [Carbon::today()->subDays(29), Carbon::today()],
            'current_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'last_month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            'current_year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'custom_date' => $fromDate && $toDate ? [Carbon::parse($fromDate), Carbon::parse($toDate)] : [null, null],
            default => [null, null],
        };
    }
}
