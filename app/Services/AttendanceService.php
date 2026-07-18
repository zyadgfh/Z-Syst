<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\EmployeeCheckedIn;
use App\Events\EmployeeCheckedOut;
use App\Models\EmployeeAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Record employee check-in via QR code
     */
    public function checkIn(User $user, int $branchId, string $ipAddress = null, string $location = null): EmployeeAttendance
    {
        return DB::transaction(function () use ($user, $branchId, $ipAddress, $location) {
            // Check if user already has an open attendance
            $openAttendance = EmployeeAttendance::where('user_id', $user->id)
                ->where('company_id', $user->company_id)
                ->where('check_out_at', null)
                ->latest()
                ->first();

            if ($openAttendance) {
                throw new \Exception('Employee already checked in. Please check out first.');
            }

            $attendance = EmployeeAttendance::create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'check_in_at' => now(),
                'check_in_method' => 'qr',
                'check_in_qr_token' => EmployeeAttendance::generateQrToken($user->id),
                'ip_address' => $ipAddress,
                'location' => $location,
            ]);

            // Dispatch check-in event
            event(new EmployeeCheckedIn($attendance));

            return $attendance;
        });
    }

    /**
     * Record employee check-out
     */
    public function checkOut(EmployeeAttendance $attendance): EmployeeAttendance
    {
        return DB::transaction(function () use ($attendance) {
            if ($attendance->check_out_at !== null) {
                throw new \Exception('Employee already checked out.');
            }

            $attendance->update([
                'check_out_at' => now(),
            ]);

            // Dispatch check-out event
            event(new EmployeeCheckedOut($attendance));

            return $attendance->fresh();
        });
    }

    /**
     * Get attendance history for a user
     */
    public function getAttendanceHistory(int $companyId, int $userId = null, int $branchId = null, array $filters = []): array
    {
        $query = EmployeeAttendance::where('company_id', $companyId)
            ->with(['user:id,name', 'branch:id,name'])
            ->orderByDesc('check_in_at');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if (isset($filters['date_from'])) {
            $query->where('check_in_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('check_in_at', '<=', $filters['date_to']);
        }

        if (isset($filters['status']) && $filters['status'] === 'open') {
            $query->where('check_out_at', null);
        }

        $perPage = $filters['per_page'] ?? 25;

        return $query->paginate($perPage)->toArray();
    }

    /**
     * Get current open attendance for a user
     */
    public function getCurrentOpenAttendance(int $userId, int $companyId): ?EmployeeAttendance
    {
        return EmployeeAttendance::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('check_out_at', null)
            ->with(['branch'])
            ->latest()
            ->first();
    }

    /**
     * Generate QR code token for check-in
     */
    public function generateCheckInToken(User $user): string
    {
        return EmployeeAttendance::generateQrToken($user->id);
    }

    /**
     * Get attendance statistics for a company
     */
    public function getStatistics(int $companyId, int $branchId = null, string $period = 'today'): array
    {
        $startDate = match ($period) {
            'today' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfDay(),
        };

        $query = EmployeeAttendance::where('company_id', $companyId)
            ->where('check_in_at', '>=', $startDate);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $attendances = $query->get();

        $totalWorkingHours = $attendances->filter(fn ($a) => $a->check_out_at !== null)
            ->sum(fn ($a) => $a->working_hours ?? 0);

        return [
            'total_check_ins' => $attendances->count(),
            'open_attendances' => $attendances->where('check_out_at', null)->count(),
            'total_working_hours' => $totalWorkingHours,
            'average_daily_hours' => $totalWorkingHours / max(1, $startDate->diffInDays(Carbon::now()) + 1),
            'by_branch' => $attendances->groupBy('branch_id')
                ->map(fn ($items, $branchId) => [
                    'branch_id' => (int) $branchId,
                    'check_ins' => $items->count(),
                    'hours' => $items->filter(fn ($i) => $i->check_out_at !== null)
                        ->sum(fn ($i) => $i->working_hours ?? 0),
                ]),
        ];
    }
}