<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HrmAddon\App\Models\LeaveType;

class AcnooLeaveTypeController extends Controller
{
    public function index()
    {
        $leave_types = LeaveType::where('business_id', auth()->user()->business_id)->latest()->paginate(10);
        return view('hrmaddon::leave-types.index',compact('leave_types'));
    }

    public function acnooFilter(Request $request)
    {
        $leave_types = LeaveType::where('business_id', auth()->user()->business_id)
                        ->when(request('search'), function($q) use($request) {
                            $q->where(function($q) use($request) {
                                $q->where('name', 'like', '%'.$request->search.'%')
                                ->orWhere('description', 'like', '%'.$request->search.'%');
                            });
                        })
                        ->latest()
                        ->paginate(10);

        if($request->ajax()){
            return response()->json([
                'data' => view('hrmaddon::leave-types.datas',compact('leave_types'))->render()
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

        LeaveType::create($request->except('business_id') +[
            'business_id' => auth()->user()->business_id
        ]);

        return response()->json([
            'message' => 'Leave Type created cuccessfully.',
            'redirect' => route('hrm.leave-types.index'),
        ]);
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $leaveType->update($request->except('business_id') + [
            'business_id' => auth()->user()->business_id
        ]);

        return response()->json([
            'message' => 'Leave Type updated successfully.',
            'redirect' => route('hrm.leave-types.index'),
        ]);
    }

    public function destroy(LeaveType $leaveType)
    {
        $leaveType->delete();

        return response()->json([
            'message' => 'Leave Type deleted successfully',
            'redirect' => route('hrm.leave-types.index')
        ]);
    }

    public function status(Request $request, $id)
    {
        $leaveType = LeaveType::findOrFail($id);
        $leaveType->update(['status' => $request->status]);
        return response()->json(['message' => 'Leave Type']);
    }

    public function deleteAll(Request $request)
    {
        $salary = LeaveType::whereIn('id', $request->input('ids'));
        $salary->delete();

         return response()->json([
             'message' => __('All Leave Types deleted successfully.'),
             'redirect' => route('hrm.leave-types.index')
         ]);
     }
}
