<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendance;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    /**
     * Check in employee via QR code
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'location' => 'nullable|string',
        ]);

        $attendance = $this->attendanceService->checkIn(
            $request->user(),
            $validated['branch_id'],
            $request->ip(),
            $validated['location']
        );

        return response()->json([
            'success' => true,
            'message' => 'Checked in successfully',
            'data' => [
                'check_in_time' => $attendance->check_in_at,
                'branch' => $attendance->branch?->name,
            ],
        ]);
    }

    /**
     * Check out employee
     */
    public function checkOut(Request $request): JsonResponse
    {
        $attendance = EmployeeAttendance::where('user_id', $request->user()->id)
            ->where('company_id', $request->user()->company_id)
            ->where('check_out_at', null)
            ->latest()
            ->firstOrFail();

        $attendance = $this->attendanceService->checkOut($attendance);

        return response()->json([
            'success' => true,
            'message' => 'Checked out successfully',
            'data' => [
                'check_out_time' => $attendance->check_out_at,
                'working_hours' => $attendance->working_hours,
            ],
        ]);
    }

    /**
     * Get attendance history
     */
    public function history(Request $request): JsonResponse
    {
        $filters = $request->only(['user_id', 'branch_id', 'date_from', 'date_to', 'status', 'per_page']);
        
        $history = $this->attendanceService->getAttendanceHistory(
            $request->user()->company_id,
            $filters['user_id'] ?? null,
            $filters['branch_id'] ?? null,
            $filters
        );

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get attendance statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $period = $request->input('period', 'today');
        
        $stats = $this->attendanceService->getStatistics(
            $request->user()->company_id,
            $request->input('branch_id'),
            $period
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Generate QR token for check-in
     */
    public function generateQrToken(Request $request): JsonResponse
    {
        $token = $this->attendanceService->generateCheckInToken($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'qr_token' => $token,
            ],
        ]);
    }
}