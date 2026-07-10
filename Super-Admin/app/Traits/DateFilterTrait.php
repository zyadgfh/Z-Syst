<?php

namespace App\Traits;

use Carbon\Carbon;

trait DateFilterTrait
{
    public function applyDateFilter($query, ?string $duration, string $dateColumn = 'created_at', $fromDate = null, $toDate = null)
    {
        switch ($duration) {
            case 'today':
                $query->whereDate($dateColumn, Carbon::today());
                break;
            case 'yesterday':
                $query->whereDate($dateColumn, Carbon::yesterday());
                break;
            case 'last_seven_days':
                $query->whereBetween($dateColumn, [
                    Carbon::today()->subDays(6)->startOfDay(),
                    Carbon::today()->endOfDay(),
                ]);
                break;
            case 'last_thirty_days':
                $query->whereBetween($dateColumn, [
                    Carbon::today()->subDays(29)->startOfDay(),
                    Carbon::today()->endOfDay(),
                ]);
                break;
            case 'current_month':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->startOfMonth()->startOfDay(),
                    Carbon::now()->endOfMonth()->endOfDay(),
                ]);
                break;
            case 'last_month':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->subMonth()->startOfMonth()->startOfDay(),
                    Carbon::now()->subMonth()->endOfMonth()->endOfDay(),
                ]);
                break;
            case 'current_year':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->startOfYear()->startOfDay(),
                    Carbon::now()->endOfYear()->endOfDay(),
                ]);
                break;
            case 'custom_date':
                if ($fromDate && $toDate) {
                    $query->whereBetween($dateColumn, [
                        Carbon::parse($fromDate)->startOfDay(),
                        Carbon::parse($toDate)->endOfDay(),
                    ]);
                }
                break;
        }

        return $query;
    }
}
