<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\DoubleEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(
        protected DoubleEntryService $accountingService
    ) {}

    // ── Accounts ──

    public function accounts(Request $request): JsonResponse
    {
        $accounts = $this->accountingService->getAccounts($request->user()->business_id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $accounts,
        ]);
    }

    public function storeAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_type_id' => 'required|exists:account_types,id',
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $account = $this->accountingService->createAccount(
            $request->user()->business_id,
            $validated
        );

        return response()->json([
            'message' => __('Account created successfully.'),
            'data' => $account->load('accountType'),
        ], 201);
    }

    public function showAccount(int $id): JsonResponse
    {
        $account = Account::where('id', $id)
            ->where('business_id', auth()->user()->business_id)
            ->with('accountType')
            ->firstOrFail();

        $balance = $account->getCurrentBalance();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => array_merge($account->toArray(), ['current_balance' => $balance]),
        ]);
    }

    // ── Journal Entries ──

    public function journalEntries(Request $request): JsonResponse
    {
        $businessId = $request->user()->business_id;

        $query = JournalEntry::where('business_id', $businessId)
            ->with(['lines.account:id,code,name', 'creator:id,name', 'poster:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->forPeriod($request->from, $request->to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $entries = $query->latest('entry_date')->paginate($request->input('per_page', 20));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $entries,
        ]);
    }

    public function storeJournalEntry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'entry_date' => 'required|date',
            'notes' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:500',
        ]);

        $entry = $this->accountingService->createJournalEntry(
            businessId: $request->user()->business_id,
            description: $validated['description'],
            lines: $validated['lines'],
            branchId: $validated['branch_id'] ?? null,
            referenceType: $validated['reference_type'] ?? null,
            referenceId: $validated['reference_id'] ?? null,
            userId: $request->user()->id,
            date: $validated['entry_date'],
        );

        return response()->json([
            'message' => __('Journal entry created successfully.'),
            'data' => $entry->load('lines.account:id,code,name'),
        ], 201);
    }

    public function showJournalEntry(int $id): JsonResponse
    {
        $entry = JournalEntry::where('id', $id)
            ->where('business_id', auth()->user()->business_id)
            ->with(['lines.account:id,code,name', 'creator:id,name', 'poster:id,name'])
            ->firstOrFail();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $entry,
        ]);
    }

    public function postJournalEntry(int $id): JsonResponse
    {
        $entry = JournalEntry::where('id', $id)
            ->where('business_id', auth()->user()->business_id)
            ->firstOrFail();

        $posted = $this->accountingService->postJournalEntry($entry, auth()->id());

        return response()->json([
            'message' => __('Journal entry posted successfully.'),
            'data' => $posted->load('lines.account:id,code,name'),
        ]);
    }

    public function voidJournalEntry(Request $request, int $id): JsonResponse
    {
        $entry = JournalEntry::where('id', $id)
            ->where('business_id', auth()->user()->business_id)
            ->firstOrFail();

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reverseEntry = $this->accountingService->voidJournalEntry($entry, $request->reason);

        return response()->json([
            'message' => __('Journal entry voided successfully.'),
            'data' => [
                'voided_entry' => $entry->fresh(),
                'reversal_entry' => $reverseEntry->load('lines.account:id,code,name'),
            ],
        ]);
    }

    // ── Reports ──

    public function trialBalance(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $data = $this->accountingService->getTrialBalance(
            $request->user()->business_id,
            $request->as_of_date ?? now()->toDateString()
        );

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $data = $this->accountingService->getIncomeStatement(
            $request->user()->business_id,
            $request->from,
            $request->to
        );

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $data = $this->accountingService->getBalanceSheet(
            $request->user()->business_id,
            $request->as_of_date ?? now()->toDateString()
        );

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function generalLedger(Request $request, int $accountId): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $data = $this->accountingService->getGeneralLedger(
            $request->user()->business_id,
            $accountId,
            $request->from,
            $request->to
        );

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }
}
