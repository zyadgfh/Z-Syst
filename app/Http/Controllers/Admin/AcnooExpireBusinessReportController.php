<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ExpiredBusinessExport;
use App\Models\Business;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class AcnooExpireBusinessReportController extends Controller
{
    public function index(Request $request)
    {
        $expired_businesses = Business::with('enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name')->whereDate('will_expire', '<', now())->latest()->paginate(10);
        return view('admin.reports.expired-business.index', compact('expired_businesses'));
    }

    public function acnooFilter(Request $request)
    {
        $search = $request->input('search');

        $expired_businesses =Business::with('enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name')->whereDate('will_expire', '<', now())->when($search, function ($q) use ($search) {
                            $q->where(function ($q) use ($search) {
                                $q->where('companyName', 'like', '%' . $search . '%')
                                    ->orWhere('phoneNumber', 'like', '%' . $search . '%')
                                    ->orWhereHas('category', function ($q) use ($search) {
                                        $q->where('name', 'like', '%' . $search . '%');
                                    })
                                    ->orWhereHas('enrolled_plan.plan', function ($q) use ($search) {
                                        $q->where('subscriptionName', 'like', '%' . $search . '%');
                                    });
                            });
                        })
                        ->latest()
                        ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.reports.expired-business.datas', compact('expired_businesses'))->render()
            ]);
        }

        return redirect(url()->previous());
    }

    public function exportExcel()
    {
        return Excel::download(new ExpiredBusinessExport, 'expired-stores.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExpiredBusinessExport, 'expired-stores.csv');
    }
}
