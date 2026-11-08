<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(protected ShiftService $shifts)
    {
        $this->middleware('permission:shifts-read')->only(['index', 'report']);
        $this->middleware('permission:shifts-create')->only(['open', 'cash']);
        $this->middleware('permission:shifts-update')->only('close');
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->user()->business_id;

        return view('admin.shifts.index', [
            'current' => $this->shifts->currentOpen($businessId),
            'shifts' => Shift::forBusiness($businessId)->latest('id')->paginate(15),
        ]);
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'opening_float' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $shift = $this->shifts->open($request->user(), (float) ($data['opening_float'] ?? 0), $data['notes'] ?? null);

        return redirect()->route('admin.shifts.index')->with('success', __('تم فتح الوردية ').$shift->shift_number);
    }

    public function cash(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'type' => 'required|in:cash_in,cash_out',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        $this->shifts->addCash($shift, $data['type'], (float) $data['amount'], $data['reason'] ?? null);

        return response()->json(['success' => true, 'expected' => $shift->recomputeExpected()]);
    }

    public function close(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'counted_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $report = $this->shifts->close($shift, (float) $data['counted_cash'], $data['notes'] ?? null);

        return response()->json(['success' => true, 'report' => $report]);
    }

    /** Z report (JSON for the receipt printer or the modal). */
    public function report(Request $request, Shift $shift)
    {
        return response()->json(['success' => true, 'report' => $shift->meta ?? $shift->recomputeExpected()]);
    }
}
