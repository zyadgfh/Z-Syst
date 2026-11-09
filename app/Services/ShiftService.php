<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Shift;
use App\Models\ShiftCashMovement;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Shift & cash drawer lifecycle: open → sell (cash auto-attributed) →
 * cash in/out → count & close (Z report with variance).
 */
class ShiftService
{
    public function open(User $user, float $openingFloat = 0.0, ?string $notes = null): Shift
    {
        $businessId = (int) $user->business_id;

        if (Shift::where('business_id', $businessId)->where('status', 'open')->exists()) {
            throw new BusinessRuleException(\App\Exceptions\Errors\ErrorCode::BUSINESS_DUPLICATE_ENTRY, __('توجد وردية مفتوحة بالفعل — أغلقها أولًا.'));
        }

        return Shift::create([
            'business_id' => $businessId,
            'user_id' => $user->id,
            'shift_number' => 'SH-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
            'opening_float' => $openingFloat,
            'status' => 'open',
            'opened_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function addCash(Shift $shift, string $type, float $amount, ?string $reason = null, ?int $saleId = null): ShiftCashMovement
    {
        if ($shift->status !== 'open') {
            throw new BusinessRuleException(\App\Exceptions\Errors\ErrorCode::BUSINESS_DUPLICATE_ENTRY, __('الوردية مقفلة.'));
        }

        return $shift->movements()->create([
            'business_id' => $shift->business_id,
            'user_id' => auth()->id(),
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
            'sale_id' => $saleId,
        ]);
    }

    /**
     * Count the drawer and close. Returns the Z-report payload.
     */
    public function close(Shift $shift, float $countedCash, ?string $notes = null): array
    {
        if ($shift->status !== 'open') {
            throw new BusinessRuleException(\App\Exceptions\Errors\ErrorCode::BUSINESS_DUPLICATE_ENTRY, __('الوردية مقفلة مسبقًا.'));
        }

        $expected = $shift->recomputeExpected();
        $difference = round($countedCash - $expected, 2);

        $report = [
            'shift_number' => $shift->shift_number,
            'opened_at' => $shift->opened_at?->toIso8601String(),
            'closed_at' => now()->toIso8601String(),
            'opening_float' => (float) $shift->opening_float,
            'expected_cash' => $expected,
            'counted_cash' => $countedCash,
            'difference' => $difference,
            'sales_total' => (float) $shift->sales()->sum('total_amount'),
            'sales_count' => (int) $shift->sales()->count(),
            'by_payment' => $shift->sales()->selectRaw('payment_type, SUM(total_amount) t')->groupBy('payment_type')->pluck('t', 'payment_type'),
            'movements' => $shift->movements()->selectRaw('type, SUM(amount) t')->groupBy('type')->pluck('t', 'type'),
        ];

        $shift->update([
            'status' => 'closed',
            'closed_at' => now(),
            'counted_cash' => $countedCash,
            'expected_cash' => $expected,
            'difference' => $difference,
            'notes' => $notes ?? $shift->notes,
            'meta' => $report,
        ]);

        return $report;
    }

    public function currentOpen(int $businessId): ?Shift
    {
        return Shift::where('business_id', $businessId)->where('status', 'open')->latest('id')->first();
    }
}
