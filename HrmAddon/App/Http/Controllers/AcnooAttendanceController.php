<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HrmAddon\App\Models\Shift;
use Modules\HrmAddon\App\Models\Employee;
use Modules\HrmAddon\App\Models\Attendance;

class AcnooAttendanceController extends Controller
{
    public function index()
    {
        $employees = Employee::where('business_id', auth()->user()->business_id)->whereStatus('active')->latest()->get();
        $attendances = Attendance::with('employee:id,name', 'shift:id,name')
                        ->where('business_id', auth()->user()->business_id)
                        ->where('month', strtolower(date('F')))
                        ->latest()
                        ->paginate(10);

        return view('hrmaddon::attendances.index', compact('attendances', 'employees'));
    }

    public function acnooFilter(Request $request)
    {
        $attendances = Attendance::with('employee:id,name', 'shift:id,name')
            ->where('business_id', auth()->user()->business_id)
            ->when($request->search, function ($query) use ($request) {
                $search = $request->search;
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                    ->orWhereHas('shift', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            })

            ->when($request->employee, function ($query) use ($request) {
                $employeeName = $request->employee;
                $query->whereHas('employee', function ($q) use ($employeeName) {
                    $q->where('name', 'like', '%' . $employeeName . '%');
                });
            })

            ->when($request->month, function ($query) use ($request) {
                $month = strtolower($request->month);
                $query->whereRaw("LOWER(month) = ?", [$month]);
            })

            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('hrmaddon::attendances.datas', compact('attendances'))->render()
            ]);
        }
        return redirect(url()->previous());
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'shift_id' => 'required|integer|exists:shifts,id',
            'date' => 'required|date',
            'month' => 'required|string',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'required|date_format:H:i',
            'duration' => 'nullable|date_format:H:i',
            'note' => 'nullable|string',
        ]);

        $timeIn = Carbon::parse($request->time_in);
        $timeOut = Carbon::parse($request->time_out);

        $durationInMinutes = $timeOut->diffInMinutes($timeIn);
        $hours = floor($durationInMinutes / 60);
        $minutes = $durationInMinutes % 60;
        $formattedDuration = sprintf('%02d:%02d', $hours, $minutes);

        Attendance::create($request->except('business_id') + [
            'business_id' => auth()->user()->business_id,
            'duration' => $formattedDuration
        ]);

        return response()->json([
            'message' => __('Attendance created successfully'),
            'redirect' => route('hrm.attendances.index')
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'shift_id' => 'required|integer|exists:shifts,id',
            'date' => 'required|date',
            'month' => 'required|string',
            'time_in' => 'required',
            'time_out' => 'required',
            'duration' => 'nullable',
            'note' => 'nullable|string',
        ]);

        $attendance = Attendance::findOrFail($id);

        $timeIn = Carbon::parse($request->time_in);
        $timeOut = Carbon::parse($request->time_out);

        $durationInMinutes = $timeOut->diffInMinutes($timeIn);
        $hours = floor($durationInMinutes / 60);
        $minutes = $durationInMinutes % 60;
        $formattedDuration = sprintf('%02d:%02d', $hours, $minutes);

        $attendance->update($request->except('business_id') + [
            'business_id' => auth()->user()->business_id,
            'duration' => $formattedDuration
        ]);

        return response()->json([
            'message' => __('Attendance Updated successfully'),
            'redirect' => route('hrm.attendances.index')
        ]);
    }

    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();
        return response()->json([
            'message' => __('Attendance Deleted Successfully'),
            'redirect' => route('hrm.attendances.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
        Attendance::whereIn('id', $request->input('ids'))->delete();

        return response()->json([
            'message' => __('All Attendances deleted successfully'),
            'redirect' => route('hrm.attendances.index')
        ]);
    }

    public function getShift()
    {
        $employee = Employee::findOrFail(request('employee_id'));
        $shift = Shift::findOrFail($employee->shift_id);

        return response()->json([
            'data' => [
                'id' => $shift->id,
                'name' => $shift->name
            ]
        ]);
    }
}
