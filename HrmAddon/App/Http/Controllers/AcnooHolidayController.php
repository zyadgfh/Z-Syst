<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\HrmAddon\App\Models\Holiday;
use Illuminate\Http\Request;

class AcnooHolidayController extends Controller
{

    public function index()
    {
        $holidays = Holiday::where('business_id', auth()->user()->business_id)->latest()->paginate(10);
        return view('hrmaddon::holidays.index', compact('holidays'));
    }

    public function acnooFilter(Request $request)
    {
        $holidays = Holiday::where('business_id', auth()->user()->business_id)
                    ->when(request('search'), function ($q) {
                        $q->where(function ($q) {
                            $q->where('name', 'like', '%' . request('search') . '%')
                                ->orWhere('description', 'like', '%' . request('search') . '%');
                        });
                    })
                    ->latest()
                    ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('hrmaddon::holidays.datas', compact('holidays'))->render()
            ]);
        }

        return redirect(url()->previous());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'description'  => 'nullable|string',
        ]);

        Holiday::create($request->except('business_id') + [
            'business_id' => auth()->user()->business_id
        ]);

        return response()->json([
            'message' => __('Holiday created successfully'),
            'redirect' => route('hrm.holidays.index')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Holiday $holiday)
    {
        $request->validate([
            'name'    => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'description'  => 'nullable|string',
        ]);

        $holiday->update($request->except('business_id') +[
            'business_id' => auth()->user()->business_id
        ]);

        return response()->json([
            'message' => __('Holiday updated successfully'),
            'redirect' => route('hrm.holidays.index')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return response()->json([
            'message' => __('Holidays Deleted Successfully'),
            'redirect' => route('hrm.holidays.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
        Holiday::whereIn('id', $request->ids)->delete();
        return response()->json([
            'message' => __('All Holiday deleted successfully.'),
            'redirect' => route('hrm.holidays.index')
        ]);
    }
}
