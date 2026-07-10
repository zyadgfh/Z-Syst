<?php

namespace Modules\HrmAddon\App\Http\Controllers;

use App\Models\PaymentType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HrmAddon\App\Models\Payroll;
use Modules\HrmAddon\App\Models\Employee;

class AcnooPayrollController extends Controller
{
    public function index()
    {
        $employees = Employee::where('business_id', auth()->user()->business_id)->whereStatus('active')->latest()->get();
        $payment_types = PaymentType::whereStatus(1)->where('business_id', auth()->user()->business_id)->latest()->get();
        $payrolls = Payroll::with('employee:id,name', 'payment_type:id,name')
            ->where('business_id', auth()->user()->business_id)
            ->where('month', strtolower(date('F')))
            ->where('payemnt_year', date('Y'))
            ->latest()
            ->paginate(10);

        return view('hrmaddon::payrolls.index', compact('payrolls', 'employees', 'payment_types'));
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
                'data' => view('hrmaddon::payrolls.datas', compact('payrolls'))->render()
            ]);
        }
        return redirect(url()->previous());
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'payment_type_id' => 'required|integer|exists:payment_types,id',
            'month' => 'required|string',
            'date' => 'nullable|date',
            'amount' => 'required|numeric',
            'note' => 'nullable|string',
            'payemnt_year' => 'required|string',
        ]);

        $exists = Payroll::where('employee_id', $request->employee_id)
            ->where('month', $request->month)
            ->where('payemnt_year', $request->payemnt_year)
            ->where('business_id', auth()->user()->business_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => __('This employee has already been paid for this month and year.'),
            ], 422);
        }


        Payroll::create($request->except('business_id') + [
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Payroll created successfully'),
            'redirect' => route('hrm.payrolls.index')
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'payment_type_id' => 'required|integer|exists:payment_types,id',
            'date' => 'nullable|date',
            'month' => 'required|string',
            'amount' => 'required|numeric',
            'note' => 'nullable|string',
            'payemnt_year' => 'required|string',
        ]);

        $payroll = Payroll::findOrFail($id);

        $exists = Payroll::where('employee_id', $request->employee_id)
            ->where('month', $request->month)
            ->where('payemnt_year', $request->payemnt_year)
            ->where('business_id', auth()->user()->business_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => __('This employee has already been paid for this month and year.'),
            ], 422);
        }


        $payroll->update($request->except('business_id') + [
            'business_id' => auth()->user()->business_id,
        ]);

        return response()->json([
            'message' => __('Payroll Updated successfully'),
            'redirect' => route('hrm.payrolls.index')
        ]);
    }

    public function destroy($id)
    {
        $payroll = Payroll::findOrFail($id);

        $payroll->delete();
        return response()->json([
            'message' => __('Payroll Deleted Successfully'),
            'redirect' => route('hrm.payrolls.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
        Payroll::whereIn('id', $request->input('ids'))->delete();

        return response()->json([
            'message' => __('All Payrolls deleted successfully'),
            'redirect' => route('hrm.payrolls.index')
        ]);
    }

    public function getEmpAmount()
    {
        $employee = Employee::findOrFail(request('employee_id'));

        return response()->json([
            'data' => [
                'amount' => $employee->amount
            ]
        ]);
    }
}
