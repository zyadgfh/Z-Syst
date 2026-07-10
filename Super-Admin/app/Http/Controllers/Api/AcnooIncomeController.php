<?php

namespace App\Http\Controllers\Api;

use App\Models\Income;
use App\Traits\DateFilterTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Events\MultiPaymentProcessed;

class AcnooIncomeController extends Controller
{
    Use DateFilterTrait;

    public function index(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $incomeQuery = Income::with(['category:id,categoryName', 'payment_type:id,name', 'branch:id,name'])->where('business_id', $businessId);

        // Branch filter
        if ($request->branch_id) {
            $incomeQuery->where('branch_id', $request->branch_id);
        }

        // Apply date filter
        if(request('duration')){
            $this->applyDateFilter($incomeQuery, request('duration'), 'incomeDate', request('from_date'), request('to_date'));
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $incomeQuery->where(function ($query) use ($search) {
                $query->where('incomeFor', 'like', '%' . $search . '%')
                    ->orWhere('paymentType', 'like', '%' . $search . '%')
                    ->orWhere('amount', 'like', '%' . $search . '%')
                    ->orWhere('referenceNo', 'like', '%' . $search . '%')
                    ->orWhereHas('category', fn($q) => $q->where('categoryName', 'like', '%' . $search . '%'))
                    ->orWhereHas('payment_type', fn($q) => $q->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('branch', fn($q) => $q->where('name', 'like', '%' . $search . '%'));
            });
        }

       $data = $incomeQuery->latest()->get();

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
            'income_category_id' => 'required|exists:income_categories,id',
            'incomeFor' => 'nullable|string',
            'referenceNo' => 'nullable|string',
            'note' => 'nullable|string',
            'payments' => 'required|array|min:1',
            'payments.*.type' => 'required|string',
            'payments.*.amount' => 'nullable|numeric|min:0',
            'payments.*.cheque_number' => 'nullable|string',
        ], [
            'payments.required' => 'At least one payment method is required.',
            'payments.*.type.required' => 'Each payment must have a type.',
            'payments.*.amount.numeric' => 'Each payment amount must be numeric.',
        ]);

        DB::beginTransaction();
        try {
            $total_amount = collect($request->payments)
                ->reject(fn($p) => strtolower($p['type'] ?? '') == 'cheque')
                ->sum(fn($p) => $p['amount'] ?? 0);

            updateBalance($total_amount, 'decrement');

            $data = Income::create($request->except('status', 'amount', 'paymentType') + [
                    'user_id' => auth()->id(),
                    'business_id' => auth()->user()->business_id,
                    'amount' => $total_amount,
                ]);

            event(new MultiPaymentProcessed(
                $request->payments ?? [],
                $data->id,
                'income',
                $total_amount ?? 0,
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
