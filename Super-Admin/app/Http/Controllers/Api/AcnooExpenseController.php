<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Events\MultiPaymentProcessed;
use App\Traits\DateFilterTrait;

class AcnooExpenseController extends Controller
{
    use DateFilterTrait;
    public function index(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $expenseQuery = Expense::with(['category:id,categoryName', 'payment_type:id,name', 'branch:id,name'])->where('business_id', $businessId);

        $expenseQuery->when($request->branch_id, function ($q) use ($request) {
            $q->where('branch_id', $request->branch_id);
        });

        // Apply date filter
        if(request('duration')){
            $this->applyDateFilter($expenseQuery, request('duration'), 'expenseDate', request('from_date'), request('to_date'));
        }

        // Search Filter
        if ($request->filled('search')) {
            $expenseQuery->where(function ($query) use ($request) {
                $query->where('expanseFor', 'like', '%' . $request->search . '%')
                    ->orWhere('paymentType', 'like', '%' . $request->search . '%')
                    ->orWhere('referenceNo', 'like', '%' . $request->search . '%')
                    ->orWhere('amount', 'like', '%' . $request->search . '%')
                    ->orWhereHas('category', function ($q) use ($request) {
                        $q->where('categoryName', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('payment_type', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('branch', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        $data = $expenseQuery->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expanseFor' => 'nullable|string',
            'referenceNo' => 'nullable|string',
            'note' => 'nullable|string',
            'payments' => 'required|array|min:1',
            'payments.*.type' => 'required|string',
            'payments.*.amount' => 'nullable|numeric|min:0',
        ], [
            'payments.required' => 'At least one payment method is required.',
            'payments.*.type.required' => 'Each payment must have a type.',
            'payments.*.amount.numeric' => 'Each payment amount must be numeric.',
        ]);

        DB::beginTransaction();
        try {

            updateBalance($request->amount, 'decrement');

            $data = Expense::create($request->except('status','paymentType') + [
                    'user_id' => auth()->id(),
                    'business_id' => auth()->user()->business_id,
                ]);

            event(new MultiPaymentProcessed(
                $request->payments ?? [],
                $data->id,
                'expense',
                $request->amount ?? 0,
            ));

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
