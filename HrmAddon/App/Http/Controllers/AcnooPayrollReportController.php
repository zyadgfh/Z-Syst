<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use Modules\HrmAddon\App\Models\Payroll;
use Modules\HrmAddon\App\Models\Employee;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AcnooPayrollReportController extends Controller
{
    public function index()
    {
        $employees = Employee::where('business_id', auth()->user()->business_id)->whereStatus('active')->latest()->get();
        $payrolls = Payroll::with('employee:id,name', 'payment_type:id,name')
                        ->where('business_id', auth()->user()->business_id)
                        ->where('month', strtolower(date('F')))
                        ->latest()
                        ->paginate(10);

        return view('hrmaddon::reports.payrolls.index', compact('payrolls', 'employees'));
    }

    public function acnooFilter(Request $request)
    {
        $payrolls = Payroll::with('employee:id,name', 'payment_type:id,name')
                    ->where('business_id', auth()->user()->business_id)
                    ->when($request->search, function ($query) use ($request) {
                        $search = $request->search;
                        $query->where(function ($query) use ($search) {
                            $query->where('puid', 'like', '%' . $search . '%')
                                  ->orWhere('amount', 'like', '%' . $search . '%')
                            ->orWhereHas('employee', function ($q) use ($search) {
                                $q->where('name', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('payment_type', function ($q) use ($search) {
                                $q->where('name', 'like', '%' . $search . '%');
                            });
                        });
                    })
                    ->when($request->employee_id, function ($query) use ($request) {
                        $search = $request->employee_id;
                        $query->where('employee_id', 'like', '%' . $search . '%');
                    })
                    ->when($request->filled('month'), function ($query) use ($request) {
                        $search = $request->month;
                        $query->where('month', 'like', '%' . $search . '%');
                    })
                    ->latest()
                    ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('hrmaddon::reports.payrolls.datas', compact('payrolls'))->render()
            ]);
        }
        return redirect(url()->previous());
    }
}
