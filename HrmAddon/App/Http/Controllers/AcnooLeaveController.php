<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HrmAddon\App\Models\Leave;
use Modules\HrmAddon\App\Models\Employee;
use Modules\HrmAddon\App\Models\LeaveType;
use Modules\HrmAddon\App\Models\Department;

class AcnooLeaveController extends Controller
{
    public function index()
    {
        $employees = Employee::where('business_id', auth()->user()->business_id)->whereStatus('active')->latest()->get();
        $departments = Department::where('business_id', auth()->user()->business_id)->whereStatus(1)->latest()->get();
        $leave_types = LeaveType::where('business_id', auth()->user()->business_id)->whereStatus(1)->latest()->get();
        $leaves = Leave::with('employee:id,name', 'leave_type:id,name', 'department:id,name')
                    ->where('business_id', auth()->user()->business_id)
                    ->where('month', strtolower(date('F')))
                    ->latest()
                    ->paginate(10);

        return view('hrmaddon::leaves.index', compact('leaves', 'employees', 'departments', 'leave_types'));
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
                'data' => view('hrmaddon::leaves.datas', compact('leaves'))->render()
            ]);
        }
        return redirect(url()->previous());
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'department_id'   => 'required|exists:departments,id',
            'leave_type_id'   => 'required|exists:leave_types,id',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'leave_duration'  => 'required|numeric|min:0.5',
            'month'           => 'required|string',
            'status'          => 'required|in:pending,approved,rejected',
            'description'     => 'nullable|string',
        ]);

        Leave::create($request->except('business_id') + [
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Leave created successfully'),
            'redirect' => route('hrm.leaves.index')
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'department_id'   => 'required|exists:departments,id',
            'leave_type_id'   => 'required|exists:leave_types,id',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'leave_duration'  => 'required|numeric|min:0.5',
            'month'           => 'required|string',
            'status'          => 'required|in:pending,approved,rejected',
            'description'     => 'nullable|string',
        ]);

        $leave = Leave::findOrFail($id);

        $leave->update($request->except('business_id') + [
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Leave Updated successfully'),
            'redirect' => route('hrm.leaves.index')
        ]);
    }

    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);
        $leave->delete();
        return response()->json([
            'message' => __('Leave Deleted Successfully'),
            'redirect' => route('hrm.leaves.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
        Leave::whereIn('id', $request->input('ids'))->delete();

        return response()->json([
            'message' => __('All Leaves deleted successfully'),
            'redirect' => route('hrm.leaves.index')
        ]);
    }

    public function status(Request $request, $id)
    {
        $order = Leave::findOrFail($id);
        $order->update(['status' => $request->status]);
        return response()->json(['message' => 'Leave']);
    }

    public function getDepartment()
    {
        $employee = Employee::findOrFail(request('employee_id'));
        $department = Department::findOrFail($employee->department_id);

        return response()->json([
            'data' => [
                'id' => $department->id,
                'name' => $department->name
            ]
        ]);
    }
}
