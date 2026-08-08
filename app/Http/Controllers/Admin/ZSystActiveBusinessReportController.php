<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ActiveStoreExport;
use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ZSystActiveBusinessReportController extends Controller
{
    public function index()
    {
        $active_stores = Business::with('enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name')->whereDate('will_expire', '>', now())->latest()->paginate(10);

        return view('admin.reports.active-stores.index', compact('active_stores'));
    }

    public function zsystFilter(Request $request)
    {
        $search = $request->input('search');

        $active_stores = Business::with('enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name')->whereDate('will_expire', '>', now())->when($search, function ($q) use ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('companyName', 'like', '%'.$search.'%')
                    ->orWhere('phoneNumber', 'like', '%'.$search.'%')
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('enrolled_plan.plan', function ($q) use ($search) {
                        $q->where('subscriptionName', 'like', '%'.$search.'%');
                    });
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.reports.active-stores.datas', compact('active_stores'))->render(),
            ]);
        }

        return redirect(url()->previous());
    }

    public function exportExcel()
    {
        return Excel::download(new ActiveStoreExport, 'active-store.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ActiveStoreExport, 'active-store.csv');
    }
}
