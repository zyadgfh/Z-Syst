<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\API\BaseController;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $expenses = Expense::forCompany($request->user()->company_id)
            ->with(['category', 'branch', 'createdBy'])
            ->when($request->branch_id, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($request->category_id, fn ($q, $v) => $q->where('expense_category_id', $v))
            ->when($request->from_date, fn ($q, $v) => $q->whereDate('expense_date', '>=', $v))
            ->when($request->to_date, fn ($q, $v) => $q->whereDate('expense_date', '<=', $v))
            ->orderByDesc('expense_date')
            ->paginate($request->per_page ?? 15);

        return $this->success($expenses);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'expense_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $expense = Expense::create(array_merge(
            $validator->validated(),
            [
                'company_id' => $request->user()->company_id,
                'created_by' => $request->user()->id,
            ]
        ));

        return $this->created($expense->load('category'), 'Expense created successfully');
    }

    public function show(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        return $this->success($expense->load(['category', 'branch', 'createdBy']));
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'sometimes|exists:branches,id',
            'expense_category_id' => 'sometimes|exists:expense_categories,id',
            'amount' => 'sometimes|numeric|min:0.01',
            'description' => 'nullable|string',
            'expense_date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $expense->update($validator->validated());

        return $this->success($expense, 'Expense updated successfully');
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        $expense->delete();

        return $this->success(null, 'Expense deleted successfully');
    }
}
