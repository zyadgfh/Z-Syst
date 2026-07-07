<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseCategoryController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $categories = ExpenseCategory::forCompany($request->user()->company_id)
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return $this->success($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $category = ExpenseCategory::create(array_merge(
            $validator->validated(),
            ['company_id' => $request->user()->company_id]
        ));

        return $this->created($category, 'Expense category created successfully');
    }

    public function show(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        if ($expenseCategory->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        return $this->success($expenseCategory->load('expenses'));
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        if ($expenseCategory->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $expenseCategory->update($validator->validated());
        return $this->success($expenseCategory, 'Expense category updated successfully');
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        if ($expenseCategory->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }
        $expenseCategory->delete();
        return $this->success(null, 'Expense category deleted successfully');
    }
}