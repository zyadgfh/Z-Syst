<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\FiscalPeriod;
use App\Models\GeneralLedger;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class DoubleEntryService
{
    /**
     * Create a journal entry with balanced debit/credit lines.
     *
     * @param array $lines  Each line: ['account_id', 'debit', 'credit', 'description?']
     */
    public function createJournalEntry(
        int $businessId,
        string $description,
        array $lines,
        ?int $branchId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $date = null,
    ): JournalEntry {
        $this->validateBalanced($lines);

        return DB::transaction(function () use ($businessId, $description, $lines, $branchId, $referenceType, $referenceId, $userId, $date) {
            $entry = JournalEntry::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'entry_number' => JournalEntry::generateEntryNumber($businessId),
                'entry_date' => $date ?? now()->toDateString(),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry;
        });
    }

    /**
     * Post a journal entry — moves it from draft to posted and updates general ledger.
     */
    public function postJournalEntry(JournalEntry $entry, ?int $userId = null): JournalEntry
    {
        if ($entry->status !== 'draft') {
            throw new \App\Exceptions\BusinessRuleException(
                'ONLY_DRAFT_POSTABLE',
                __('Only draft entries can be posted.'),
                ['entry_number' => $entry->entry_number, 'current_status' => $entry->status]
            );
        }

        if (!$entry->isBalanced()) {
            throw new \App\Exceptions\BusinessRuleException(
                'UNBALANCED_ENTRY',
                __('Journal entry debits must equal credits.'),
                ['entry_number' => $entry->entry_number]
            );
        }

        return DB::transaction(function () use ($entry, $userId) {
            $runningBalances = [];

            foreach ($entry->lines as $line) {
                $accountId = $line->account_id;
                $previousBalance = $runningBalances[$accountId] ?? $this->getAccountBalance($accountId, $entry->business_id);

                $account = Account::find($accountId);
                $isDebitPositive = $account->accountType->is_debit_positive;

                if ($isDebitPositive) {
                    $newBalance = $previousBalance + (float) $line->debit - (float) $line->credit;
                } else {
                    $newBalance = $previousBalance + (float) $line->credit - (float) $line->debit;
                }

                GeneralLedger::create([
                    'business_id' => $entry->business_id,
                    'account_id' => $accountId,
                    'journal_entry_id' => $entry->id,
                    'journal_entry_line_id' => $line->id,
                    'transaction_date' => $entry->entry_date,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'balance' => $newBalance,
                ]);

                $runningBalances[$accountId] = $newBalance;
            }

            $entry->update([
                'status' => 'posted',
                'posted_by' => $userId ?? $entry->created_by,
                'posted_at' => now(),
            ]);

            return $entry->fresh();
        });
    }

    /**
     * Void a posted journal entry by reversing it.
     */
    public function voidJournalEntry(JournalEntry $entry, ?string $reason = null): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw new \App\Exceptions\BusinessRuleException(
                'ONLY_POSTED_VOIDABLE',
                __('Only posted entries can be voided.'),
                ['entry_number' => $entry->entry_number, 'current_status' => $entry->status]
            );
        }

        return DB::transaction(function () use ($entry, $reason) {
            // Create reverse entry
            $reverseLines = $entry->lines->map(fn($line) => [
                'account_id' => $line->account_id,
                'debit' => $line->credit,  // swap
                'credit' => $line->debit,  // swap
                'description' => "Reversal of {$entry->entry_number}: {$line->description}",
            ])->toArray();

            $reverseEntry = $this->createJournalEntry(
                businessId: $entry->business_id,
                description: "VOID: {$entry->description}" . ($reason ? " — {$reason}" : ''),
                lines: $reverseLines,
                branchId: $entry->branch_id,
                referenceType: JournalEntry::class,
                referenceId: $entry->id,
                userId: $entry->posted_by,
                date: now()->toDateString(),
            );

            $this->postJournalEntry($reverseEntry, $entry->posted_by);

            $entry->update(['status' => 'voided']);

            return $reverseEntry;
        });
    }

    /**
     * Get trial balance for a business at a given date.
     */
    public function getTrialBalance(int $businessId, ?string $asOfDate = null): array
    {
        $accounts = Account::where('business_id', $businessId)
            ->where('is_active', true)
            ->with('accountType')
            ->orderBy('code')
            ->get();

        $result = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $query = GeneralLedger::where('account_id', $account->id)
                ->where('business_id', $businessId);

            if ($asOfDate) {
                $query->where('transaction_date', '<=', $asOfDate);
            }

            $totals = $query->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')->first();

            $debit = (float) ($totals->total_debit ?? 0);
            $credit = (float) ($totals->total_credit ?? 0);
            $opening = (float) $account->opening_balance;

            if ($account->accountType->is_debit_positive) {
                $balance = $opening + $debit - $credit;
            } else {
                $balance = $opening + $credit - $debit;
            }

            if (abs($balance) < 0.01 && $debit == 0 && $credit == 0 && $opening == 0) {
                continue; // Skip zero-balance accounts
            }

            $result[] = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->accountType->name,
                'opening_balance' => $opening,
                'total_debit' => $debit,
                'total_credit' => $credit,
                'balance' => $balance,
                'balance_type' => $balance >= 0 ? 'debit' : 'credit',
            ];            if ($account->accountType->is_debit_positive) {
                if ($balance >= 0) {
                    $totalDebit += abs($balance);
                } else {
                    $totalCredit += abs($balance);
                }
            } else {
                // Credit-positive accounts: positive balance = credit
                if ($balance >= 0) {
                    $totalCredit += abs($balance);
                } else {
                    $totalDebit += abs($balance);
                }
            }
        }

        return [
            'accounts' => $result,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'as_of_date' => $asOfDate ?? now()->toDateString(),
        ];
    }

    /**
     * Get General Ledger for a specific account.
     */
    public function getGeneralLedger(int $businessId, int $accountId, ?string $from = null, ?string $to = null): array
    {
        $account = Account::where('id', $accountId)
            ->where('business_id', $businessId)
            ->with('accountType')
            ->firstOrFail();

        $query = GeneralLedger::where('account_id', $accountId)
            ->where('business_id', $businessId)
            ->with(['journalEntry:id,entry_number,entry_date,description,status']);

        if ($from) {
            $query->where('transaction_date', '>=', $from);
        }
        if ($to) {
            $query->where('transaction_date', '<=', $to);
        }

        $entries = $query->orderBy('transaction_date')->orderBy('id')->get();

        $openingBalance = (float) $account->opening_balance;

        // Calculate opening balance from entries before $from
        if ($from) {
            $previousBalance = GeneralLedger::where('account_id', $accountId)
                ->where('business_id', $businessId)
                ->where('transaction_date', '<', $from)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            if ($account->accountType->is_debit_positive) {
                $openingBalance += (float) ($previousBalance->total_debit ?? 0) - (float) ($previousBalance->total_credit ?? 0);
            } else {
                $openingBalance += (float) ($previousBalance->total_credit ?? 0) - (float) ($previousBalance->total_debit ?? 0);
            }
        }

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->accountType->name,
            ],
            'opening_balance' => $openingBalance,
            'entries' => $entries,
            'closing_balance' => $entries->last()->balance ?? $openingBalance,
        ];
    }

    /**
     * Get Income Statement (Profit & Loss) for a period.
     */
    public function getIncomeStatement(int $businessId, string $from, string $to): array
    {
        $revenueAccounts = Account::where('business_id', $businessId)
            ->where('is_active', true)
            ->whereHas('accountType', fn($q) => $q->where('name', AccountType::REVENUE))
            ->orderBy('code')
            ->get();

        $expenseAccounts = Account::where('business_id', $businessId)
            ->where('is_active', true)
            ->whereHas('accountType', fn($q) => $q->where('name', AccountType::EXPENSE))
            ->orderBy('code')
            ->get();

        $revenue = $this->sumAccountBalances($revenueAccounts, $businessId, $from, $to);
        $expenses = $this->sumAccountBalances($expenseAccounts, $businessId, $from, $to);

        return [
            'period' => ['from' => $from, 'to' => $to],
            'revenue' => $revenue,
            'total_revenue' => $revenue['total'],
            'expenses' => $expenses,
            'total_expenses' => $expenses['total'],
            'net_income' => round($revenue['total'] - $expenses['total'], 2),
        ];
    }

    /**
     * Get Balance Sheet as of a date.
     */
    public function getBalanceSheet(int $businessId, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        $assetAccounts = Account::where('business_id', $businessId)
            ->where('is_active', true)
            ->whereHas('accountType', fn($q) => $q->where('name', AccountType::ASSET))
            ->orderBy('code')->get();

        $liabilityAccounts = Account::where('business_id', $businessId)
            ->where('is_active', true)
            ->whereHas('accountType', fn($q) => $q->where('name', AccountType::LIABILITY))
            ->orderBy('code')->get();

        $equityAccounts = Account::where('business_id', $businessId)
            ->where('is_active', true)
            ->whereHas('accountType', fn($q) => $q->where('name', AccountType::EQUITY))
            ->orderBy('code')->get();

        $assets = $this->sumAccountBalances($assetAccounts, $businessId, null, $asOfDate);
        $liabilities = $this->sumAccountBalances($liabilityAccounts, $businessId, null, $asOfDate);
        $equity = $this->sumAccountBalances($equityAccounts, $businessId, null, $asOfDate);

        return [
            'as_of_date' => $asOfDate,
            'assets' => $assets,
            'total_assets' => $assets['total'],
            'liabilities' => $liabilities,
            'total_liabilities' => $liabilities['total'],
            'equity' => $equity,
            'total_equity' => $equity['total'],
            'is_balanced' => abs($assets['total'] - $liabilities['total'] - $equity['total']) < 0.01,
        ];
    }

    /**
     * Get all accounts for a business.
     */
    public function getAccounts(int $businessId): \Illuminate\Support\Collection
    {
        return Account::where('business_id', $businessId)
            ->with('accountType')
            ->orderBy('code')
            ->get();
    }

    /**
     * Create an account for a business.
     */
    public function createAccount(int $businessId, array $data): Account
    {
        return Account::create($data + ['business_id' => $businessId]);
    }

    /**
     * Validate debits equal credits.
     */
    private function validateBalanced(array $lines): void
    {
        $totalDebit = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));

        if (abs($totalDebit - $totalCredit) >= 0.01) {
            throw new \App\Exceptions\BusinessRuleException(
                'UNBALANCED_ENTRY',
                __('Journal entry debits must equal credits. Debit: :debit, Credit: :credit', [
                    'debit' => number_format($totalDebit, 2),
                    'credit' => number_format($totalCredit, 2),
                ]),
                ['total_debit' => $totalDebit, 'total_credit' => $totalCredit]
            );
        }
    }

    /**
     * Get current balance of an account from general ledger.
     */
    private function getAccountBalance(int $accountId, int $businessId): float
    {
        $lastEntry = GeneralLedger::where('account_id', $accountId)
            ->where('business_id', $businessId)
            ->latest('id')
            ->value('balance');

        return $lastEntry !== null ? (float) $lastEntry : 0;
    }

    /**
     * Sum balances for a collection of accounts in a period.
     */
    private function sumAccountBalances($accounts, int $businessId, ?string $from, ?string $to): array
    {
        $items = [];
        $total = 0;

        foreach ($accounts as $account) {
            $query = GeneralLedger::where('account_id', $account->id)
                ->where('business_id', $businessId);

            if ($from) {
                $query->where('transaction_date', '>=', $from);
            }
            if ($to) {
                $query->where('transaction_date', '<=', $to);
            }

            $totals = $query->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')->first();

            $debit = (float) ($totals->total_debit ?? 0);
            $credit = (float) ($totals->total_credit ?? 0);

            if ($account->accountType->is_debit_positive) {
                $balance = $debit - $credit;
            } else {
                $balance = $credit - $debit;
            }

            if (abs($balance) >= 0.01) {
                $items[] = [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => round($balance, 2),
                ];
                $total += $balance;
            }
        }

        return [
            'items' => $items,
            'total' => round($total, 2),
        ];
    }
}
