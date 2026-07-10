<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HrmAddon\App\Models\Designation;

class AcnooDesignationController extends Controller
{
    public function index()
    {
        $designations = Designation::where('business_id', auth()->user()->business_id)->latest()->paginate(20);
        return view('hrmaddon::designations.index', compact('designations'));
    }
    public function acnooFilter(Request $request)
    {
        $designations = Designation::where('business_id', auth()->user()->business_id)
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
                'data' => view('hrmaddon::designations.datas',compact('designations'))->render()
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

        Designation::create($request->except('business_id') + [
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Designation created cuccessfully'),
            'redirect' => route('hrm.designations.index'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $designation = Designation::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $designation->update($request->all());

        return response()->json([
            'message' => __('Designation updated successfully'),
            'redirect' => route('hrm.designations.index'),
        ]);
    }

    public function destroy($id)
    {
        $designation = Designation::findOrFail($id);

        $designation->delete();

        return response()->json([
            'message' => __('Designation deleted successfully'),
            'redirect' => route('hrm.designations.index')
        ]);
    }

    public function status(Request $request, $id)
    {
        $designation = Designation::findOrFail($id);
        $designation->update(['status' => $request->status]);
        return response()->json(['message' => __('Designation')]);
    }

    public function deleteAll(Request $request)
    {
        Designation::whereIn('id', $request->input('ids'))->delete();

        return response()->json([
            'message' => __('Selected Designations deleted successfully'),
            'redirect' => route('hrm.designations.index')
        ]);
    }
}
