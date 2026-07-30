<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\PlanSubscribe;
use Illuminate\Support\Facades\DB;
use App\Exports\ManualPaymentExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class ZSystManualPaymentReportController extends Controller
{
    public function index()
    {
        $manual_payments = PlanSubscribe::with([
            'plan:id,subscriptionName',
            'business:id,companyName,pictureUrl,business_category_id',
            'business.category:id,name',
            'gateway:id,name'
        ])->whereHas('gateway', function ($query) {
            $query->where('name', 'Manual');
        })->latest()->paginate(10);

        return view('admin.manual-payments.index', compact('manual_payments'));
    }

    public function zsystFilter(Request $request)
    {
        $search = $request->input('search');

        $manual_payments = PlanSubscribe::with([
            'plan:id,subscriptionName',
            'business:id,companyName,pictureUrl,business_category_id',
            'business.category:id,name',
            'gateway:id,name'
        ])->whereHas('gateway', function ($query) {
            $query->where('name', 'Manual');
        })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('duration', 'like', '%' . $search . '%')
                        ->orWhereHas('plan', function ($q) use ($search) {
                            $q->where('subscriptionName', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('gateway', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('business', function ($q) use ($search) {
                            $q->where('companyName', 'like', '%' . $search . '%')
                                ->orWhereHas('category', function ($q) use ($search) {
                                    $q->where('name', 'like', '%' . $search . '%');
                                });
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.manual-payments.datas', compact('manual_payments'))->render()
            ]);
        }

        return redirect(url()->previous());
    }


    public function reject(Request $request, string $id)
    {
        $request->validate([
            'notes' => 'required|string|max:255',
        ]);

        $reject = PlanSubscribe::findOrFail($id);

        if ($reject) {
            $reject->update([
                'payment_status' => 'rejected',
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => 'Status Rejected',
                'redirect' => route('admin.manual-payments.index'),
            ]);
        } else {
            return response()->json(['message' => 'request not found'], 404);
        }
    }

    public function paid(Request $request, string $id)
    {
        $request->validate([
            'notes' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $subscribe = PlanSubscribe::findOrFail($id);

            $existingNotes = $subscribe->notes ?? [];
            $updatedNotes = array_merge($existingNotes, ['reason' => $request->notes]);

            $subscribe->update($request->except('notes') + [
                    'payment_status' => 'paid',
                    'notes' => $updatedNotes,
                ]);

            $subscribe->business->update([
                'subscriptionDate' => now(),
                'plan_subscribe_id' => $subscribe->id,
                'will_expire' => now()->addDays($subscribe->plan->duration),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Status Paid',
                'redirect' => route('admin.manual-payments.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'request not found'], 404);
        }
    }

    public function getInvoice($invoice_id)
    {
        $manual_payment = PlanSubscribe::with(['plan:id,subscriptionName','business:id,companyName,business_category_id,phoneNumber,address','business.category:id,name','gateway:id,name'])->findOrFail($invoice_id);
        return view('admin.manual-payments.invoice', compact('manual_payment'));
    }

    public function exportExcel()
    {
        return Excel::download(new ManualPaymentExport, 'manual-payments.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ManualPaymentExport, 'manual-payments.csv');
    }
}
