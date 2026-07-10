<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HrmAddon\App\Models\Shift;

class AcnooShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::where('business_id', auth()->user()->business_id)->latest()->paginate(10);
        return view('hrmaddon::shifts.index', compact('shifts'));
    }

    public function acnooFilter(Request $request)
    {
        $shifts = Shift::where('business_id', auth()->user()->business_id)
                    ->when(request('search'), function ($q) use ($request) {
                    $q->where(function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('start_time', 'like', '%' . $request->search . '%')
                        ->orWhere('end_time', 'like', '%' . $request->search . '%');
                    });
                })
                ->latest()
                ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('hrmaddon::shifts.datas', compact('shifts'))->render()
            ]);
        }
        return redirect(url()->previous());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:250',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'start_break_time' => 'nullable',
            'end_break_time' => 'nullable',
            'break_status' => 'nullable|string',
            'status' => 'nullable|boolean',
            'break_time' => 'nullable',
        ]);

        if ($request->filled('start_break_time') && $request->filled('end_break_time')) {
            $startTime = Carbon::parse($request->input('start_break_time'));
            $endTime = Carbon::parse($request->input('end_break_time'));

            $hoursDiff = $endTime->diffInHours($startTime);
            $minutesDiff = $endTime->diffInMinutes($startTime) % 60;

            $breakTime = sprintf('%02d:%02d', $hoursDiff, $minutesDiff);
        } else {
            $breakTime = null;
        }

        Shift::create($request->except('status','break_time', 'business_id')+[
                'business_id' => auth()->user()->business_id,
                'break_time' => $breakTime,
            ]);

        return response()->json([
            'message' => __('Shift created successfully.'),
            'redirect' => route('hrm.shifts.index')
        ]);
    }

    public function update(Request $request, Shift $shift)
    {
        $request->validate([
            'name' => 'required|string|max:250',
            'start_time' => 'required',
            'end_time' => 'required',
            'start_break_time' => 'nullable',
            'end_break_time' => 'nullable',
            'break_status' => 'nullable|string',
            'status' => 'nullable|boolean',
            'break_time' => 'nullable',
        ]);

        if ($request->filled('start_break_time') && $request->filled('end_break_time')) {
            $startTime = \Carbon\Carbon::parse($request->input('start_break_time'));
            $endTime = \Carbon\Carbon::parse($request->input('end_break_time'));

            $hoursDiff = $endTime->diffInHours($startTime);
            $minutesDiff = $endTime->diffInMinutes($startTime) % 60;

            $breakTime = sprintf('%02d:%02d', $hoursDiff, $minutesDiff);
        } else {
            $breakTime = null;
        }

        $shift->update($request->except('status','break_time', 'business_id')+[
            'business_id' => auth()->user()->business_id,
            'break_time' => $breakTime,
        ]);

        return response()->json([
            'message' => __('Shift Updated successfully.'),
            'redirect' => route('hrm.shifts.index')
        ]);
    }

    public function destroy($id)
    {
        $shift = Shift::findOrFail($id);
        $shift->delete();
        return response()->json([
            'message' => __('Shift deleted successfully.'),
            'redirect' => route('hrm.shifts.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
        Shift::whereIn('id', $request->input('ids'))->delete();

        return response()->json([
            'message' => __('All shifts deleted successfully'),
            'redirect' => route('hrm.shifts.index')
        ]);
    }

    public function status(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);
        $shift->update(['status' => $request->status]);
        return response()->json(['message' => 'Shift']);
    }
}
