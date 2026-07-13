<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $registers = CashRegister::where('company_id', $request->user()->company_id)
            ->with(['branch:id,name', 'user:id,name'])
            ->when($request->branch_id, fn($q, $v) => $q->where('branch_id', $v))
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('opened_at', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('opened_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($registers);
    }

    public function open(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Check if user already has an open register
        $existing = CashRegister::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You already have an open cash register'], 422);
        }

        $register = CashRegister::create([
            'company_id' => $request->user()->company_id,
            'branch_id' => $request->branch_id,
            'user_id' => $request->user()->id,
            'opening_balance' => $request->opening_balance,
            'status' => 'open',
            'notes' => $request->notes,
            'opened_at' => now(),
        ]);

        return response()->json($register->load(['branch:id,name']), 201);
    }

    public function close(Request $request, CashRegister $cashRegister): JsonResponse
    {
        if ($cashRegister->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($cashRegister->status !== 'open') {
            return response()->json(['message' => 'Cash register is already closed'], 422);
        }

        $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $expectedBalance = $cashRegister->opening_balance
            + ($cashRegister->total_sales ?? 0)
            - ($cashRegister->total_returns ?? 0)
            - ($cashRegister->total_expenses ?? 0);

        $difference = $request->closing_balance - $expectedBalance;

        $cashRegister->update([
            'closing_balance' => $request->closing_balance,
            'expected_balance' => $expectedBalance,
            'difference' => $difference,
            'status' => 'closed',
            'notes' => $request->notes ?? $cashRegister->notes,
            'closed_at' => now(),
        ]);

        return response()->json($cashRegister->fresh());
    }

    public function current(Request $request): JsonResponse
    {
        $register = CashRegister::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->with(['branch:id,name'])
            ->first();

        if (!$register) {
            return response()->json(['message' => 'No open cash register found'], 404);
        }

        return response()->json($register);
    }

    public function summary(Request $request): JsonResponse
    {
        $query = CashRegister::where('company_id', $request->user()->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $startDate = $request->date_from ? now()->parse($request->date_from) : now()->startOfDay();
        $endDate = $request->date_to ? now()->parse($request->date_to) : now()->endOfDay();

        $registers = $query->whereBetween('opened_at', [$startDate, $endDate])->get();

        return response()->json([
            'total_registers' => $registers->count(),
            'open_registers' => $registers->where('status', 'open')->count(),
            'closed_registers' => $registers->where('status', 'closed')->count(),
            'total_opening_balance' => $registers->sum('opening_balance'),
            'total_closing_balance' => $registers->sum('closing_balance'),
            'total_sales' => $registers->sum('total_sales'),
            'total_returns' => $registers->sum('total_returns'),
            'total_expenses' => $registers->sum('total_expenses'),
            'total_difference' => $registers->sum('difference'),
        ]);
    }
}