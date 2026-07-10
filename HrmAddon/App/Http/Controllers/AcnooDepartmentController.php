<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HrmAddon\App\Models\Department;

class AcnooDepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::where('business_id', auth()->user()->business_id)->latest()->paginate(20);
        return view('hrmaddon::department.index', compact('departments'));
    }

    public function acnooFilter(Request $request)
    {
        $departments = Department::where('business_id', auth()->user()->business_id)
        ->when(request('search'), function($q) use($request) {
                $q->where(function($q) use($request) {
                    $q->where('name', 'like', '%'.$request->search.'%')
                      ->orWhere('description', 'like', '%'.$request->search.'%');
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if($request->ajax()){
            return response()->json([
                'data' => view('hrmaddon::department.datas',compact('departments'))->render()
            ]);
        }
        return redirect(url()->previous());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Department::create($request->except('business_id') + [
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Department created cuccessfully'),
            'redirect' => route('hrm.department.index'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Department::where('id', $id)->update($request->except(['_token', '_method']));

        return response()->json([
            'message' => __('Department updated successfully'),
            'redirect' => route('hrm.department.index'),
        ]);
    }

    public function destroy($id)
    {
        Department::where('id', $id)->delete();

        return response()->json([
            'message' => __('Department deleted successfully'),
            'redirect' => route('hrm.department.index')
        ]);
    }

    public function status(Request $request, $id)
    {
        $department = Department::findOrFail($id);
        $department->update(['status' => $request->status]);
        return response()->json(['message' => __('Department')]);
    }

    public function deleteAll(Request $request)
    {
        Department::whereIn('id', $request->input('ids'))->delete();

        return response()->json([
            'message' => __('Selected Departments deleted successfully'),
            'redirect' => route('hrm.department.index')
        ]);
    }
}
