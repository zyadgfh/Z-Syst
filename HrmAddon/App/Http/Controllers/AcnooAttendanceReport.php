<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HrmAddon\App\Models\Employee;
use Modules\HrmAddon\App\Models\Attendance;

class AcnooAttendanceReport extends Controller
{
    public function index()
    {
        $employees = Employee::where('business_id', auth()->user()->business_id)->whereStatus('active')->latest()->get();
        $attendances = Attendance::with('employee:id,name', 'shift:id,name')
                        ->where('business_id', auth()->user()->business_id)
                        ->where('month', strtolower(date('F')))
                        ->latest()
                        ->paginate(10);

        return view('hrmaddon::reports.attendances.index', compact('attendances', 'employees'));
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
                'data' => view('hrmaddon::reports.attendances.datas', compact('attendances'))->render()
            ]);
        }
        return redirect(url()->previous());
    }
}
