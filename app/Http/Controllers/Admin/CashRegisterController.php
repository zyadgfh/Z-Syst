<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\API\BaseController;
use App\Models\CashRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CashRegisterController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $registers = CashRegister::forCompany($request->user()->company_id)
            ->with(['branch', 'user'])
            ->when($request->branch_id, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->user_id, fn ($q, $v) => $q->where('user_id', $v))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15);

        return $this->success($registers);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        // Check if user already has an open register
        $existingOpen = CashRegister::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if ($existingOpen) {
            return $this->error('You already have an open cash register. Please close it first.', 422);
        }

        $register = CashRegister::create([
            'company_id' => $request->user()->company_id,
            'branch_id' => $request->branch_id,
            'user_id' => $request->user()->id,
            'opening_balance' => $request->opening_balance,
            'notes' => $request->notes,
            'status' => 'open',
        ]);

        return $this->created($register->load('branch'), 'Cash register opened successfully');
    }

    public function show(Request $request, CashRegister $cashRegister): JsonResponse
    {
        if ($cashRegister->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        return $this->success($cashRegister->load(['branch', 'user']));
    }

    public function close(Request $request, CashRegister $cashRegister): JsonResponse
    {
        if ($cashRegister->company_id !== $request->user()->company_id) {
            return $this->error('Forbidden', 403);
        }

        if ($cashRegister->status === 'closed') {
            return $this->error('Cash register is already closed', 422);
        }

        $validator = Validator::make($request->all(), [
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $expectedBalance = $cashRegister->opening_balance + $cashRegister->total_sales - $cashRegister->total_returns - $cashRegister->total_expenses;
        $difference = $request->closing_balance - $expectedBalance;

        $cashRegister->update([
            'closing_balance' => $request->closing_balance,
            'expected_balance' => $expectedBalance,
            'difference' => $difference,
            'notes' => $request->notes ? $cashRegister->notes."\n".$request->notes : $cashRegister->notes,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return $this->success($cashRegister, 'Cash register closed successfully');
    }
}
