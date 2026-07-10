<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HrmAddon\App\Models\Leave;
use Modules\HrmAddon\App\Models\Employee;

class AcnooLeaveReportController extends Controller
{
    public function index()
    {
        $employees = Employee::where('business_id', auth()->user()->business_id)->whereStatus('active')->latest()->get();
        $leaves = Leave::with('employee:id,name', 'leave_type:id,name', 'department:id,name')
                    ->where('business_id', auth()->user()->business_id)
                    ->where('month', strtolower(date('F')))
                    ->latest()
                    ->paginate(10);

        return view('hrmaddon::reports.leaves.index', compact('leaves', 'employees'));
    }

    public function acnooFilter(Request $request)
    {
        $leaves = Leave::with('employee:id,name', 'leave_type:id,name', 'department:id,name')
            ->where('business_id', auth()->user()->business_id)
            ->when($request->search, function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', '%' . $search . '%')
                        ->orWhere('start_date', 'like', '%' . $search . '%')
                        ->orWhere('end_date', 'like', '%' . $search . '%')
                        ->orWhere('leave_duration', 'like', '%' . $search . '%')
                        ->orWhere('month', 'like', '%' . $search . '%')
                        ->orWhere('status', 'like', '%' . $search . '%')
                        ->orWhereHas('employee', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('department', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('leave_type', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        });
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
                'data' => view('hrmaddon::reports.leaves.datas', compact('leaves'))->render()
            ]);
        }
        return redirect(url()->previous());
    }
}
