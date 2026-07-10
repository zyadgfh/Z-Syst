<?php

namespace App\Http\Controllers\Admin;

use App\Models\Business;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AcnooAffiliateReportController extends Controller
{
    public function index()
    {
        $items = Business::with(['enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name'])->latest()->paginate(20);
        return view('admin.affiliate-modules.reports.index', compact('items'));
    }

    public function acnooFilter(Request $request)
    {
        $search = $request->input('search');

        $items = Business::with(['enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name'])->when($search, function ($q) use ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('companyName', 'like', '%' . $search . '%')
                    ->orWhereHas('enrolled_plan.plan', function ($q) use ($search) {
                        $q->where('subscriptionName', 'like', '%' . $search . '%');
                    });
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 20);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.affiliate-modules.reports.datas', compact('items'))->render()
            ]);
        }

        return redirect(url()->previous());
    }
}
