<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HrmAddon\App\Models\Shift;
use Illuminate\Support\Facades\Storage;
use Modules\HrmAddon\App\Models\Employee;
use Modules\HrmAddon\App\Models\Department;
use Modules\HrmAddon\App\Models\Designation;

class AcnooEmployeeController extends Controller
{
    use HasUploader;

    public function index()
    {
        $employees = Employee::with('department:id,name', 'designation:id,name', 'shift:id,name')
                        ->where('business_id', auth()->user()->business_id)
                        ->latest()->paginate(10);

        return view('hrmaddon::employees.index', compact('employees'));
    }

    public function acnooFilter(Request $request)
    {
        $employees = Employee::with('department', 'designation', 'shift')
                        ->where('business_id', auth()->user()->business_id)
                        ->when(request('search'), function ($q) {
                            $q->where('phone', 'like', '%' . request('search') . '%')
                                ->orWhere('amount', 'like', '%' . request('search') . '%')
                                ->orWhere('name', 'like', '%' . request('search') . '%');
                            $q->orwhereHas('department', function($d) {
                                $d->where('name', 'like', '%'.request('search').'%');
                            });
                            $q->orwhereHas('designation', function($r) {
                                $r->where('name', 'like', '%'.request('search').'%');
                            });
                            $q->orwhereHas('shift', function($s) {
                                $s->where('name', 'like', '%'.request('search').'%');
                            });
                        })
                        ->latest()
                        ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('hrmaddon::employees.datas', compact('employees'))->render()
            ]);
        }

        return redirect(url()->previous());
    }


    public function create()
    {
        $designations = Designation::where('business_id', auth()->user()->business_id)->where('status', 1)->get();
        $departments = Department::where('business_id', auth()->user()->business_id)->where('status', 1)->get();
        $shifts = Shift::where('business_id', auth()->user()->business_id)->where('status', 1)->get();

        return view('hrmaddon::employees.create', compact('designations', 'departments', 'shifts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'string|required',
            'designation_id' => 'integer|required|exists:designations,id',
            'department_id' => 'integer|required|exists:departments,id',
            'shift_id' => 'integer|required|exists:shifts,id',
            'gender' => 'string|required',
            'birth_date' => 'string|date',
            'join_date' => 'string|date',
            'email' => 'string|nullable',
            'phone' => 'numeric|required',
            'amount' => 'numeric|required',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,svg|max:1024',
        ]);

        Employee::create($request->except('business_id', 'image') +[
            'business_id' => auth()->user()->business_id,
            'image' => $request->image ? $this->upload($request, 'image') : NULL
        ]);

        return response()->json([
            'message' => __('Employee_created Successfully'),
            'redirect' => route('hrm.employees.index')
        ]);
    }


    public function edit(string $id)
    {
        $employee = Employee::findOrFail($id);
        $designations = Designation::where('business_id', auth()->user()->business_id)->where('status', 1)->get();
        $departments = Department::where('business_id', auth()->user()->business_id)->where('status', 1)->get();
        $shifts = Shift::where('business_id', auth()->user()->business_id)->where('status', 1)->get();

        return view('hrmaddon::employees.edit', compact('employee', 'designations', 'departments', 'shifts'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'string|required',
            'designation_id' => 'integer|required|exists:designations,id',
            'department_id' => 'integer|required|exists:departments,id',
            'shift_id' => 'integer|required|exists:shifts,id',
            'gender' => 'string|required',
            'birth_date' => 'string|date',
            'join_date' => 'string|date',
            'email' => 'string|nullable',
            'phone' => 'numeric|required',
            'amount' => 'numeric|required',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,svg|max:1024',
        ]);

        $employee = Employee::findOrFail($id);

        $employee->update($request->except('business_id', 'image') +[
            'business_id' => auth()->user()->business_id,
            'image' => $request->image ? $this->upload($request, 'image', $employee->image) : $employee->image
        ]);

        return response()->json([
            'message' => __('Employee updated Successfully'),
            'redirect' => route('hrm.employees.index')
        ]);
    }

    public function destroy(string $id)
    {
        $employee = Employee::findOrFail($id);
        if(file_exists($employee->image)) {
            Storage::delete($employee->image);
        }
        $employee->delete();

        return response()->json([
            'message' => __('Employee deleted successfully'),
            'redirect' => route('hrm.employees.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
       $employees = Employee::whereIn('id', $request->ids)->get();

        foreach($employees as $employee) {
            if(file_exists($employee->image)) {
                Storage::delete($employee->image);
            }
        }
        Employee::whereIn('id', $request->ids)->delete();
        return response()->json([
            'message'   => __('Selected employees deleted successfully'),
            'redirect'  => route('hrm.employees.index')
        ]);
    }
}
