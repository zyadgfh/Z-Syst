<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

/**
 * Light double-entry accounting: a seeded chart of accounts plus automatic
 * journal postings from sales / purchases / expenses, and a VAT report.
 */
class AccountingService
{
    public const CHART = [
        ['1010', 'الصندوق (نقدية)', 'asset'],
        ['1020', 'البنك', 'asset'],
        ['1030', 'المخزون', 'asset'],
        ['1040', 'مدينون (عملاء)', 'asset'],
        ['2010', 'الدائنون (موردون)', 'liability'],
        ['2020', 'ضريبة القيمة المضافة المستحقة', 'liability'],
        ['3010', 'رأس المال', 'equity'],
        ['4010', 'إيرادات المبيعات', 'revenue'],
        ['4020', 'إيرادات أخرى', 'revenue'],
        ['5010', 'تكلفة المبيعات', 'expense'],
        ['5020', 'مصروفات تشغيلية', 'expense'],
    ];

    public function ensureChart(int $businessId): void
    {
        foreach (self::CHART as [$code, $name, $type]) {
            Account::firstOrCreate(['business_id' => $businessId, 'code' => $code], [
                'name' => $name, 'type' => $type, 'is_system' => true,
            ]);
        }
    }

    /** Sale → revenue + VAT + cash/receivables, and COGS. */
    public function postSale(Sale $sale): JournalEntry
    {
        $businessId = (int) $sale->business_id;
        $this->ensureChart($businessId);

        $total = (float) $sale->totalAmount;
        $paid = (float) $sale->paidAmount;
        $due = (float) $sale->dueAmount;
        $cost = (float) $sale->details()->sum(DB::raw('purchase_price * quantities'));

        $lines = [
            ['account_code' => '4010', 'debit' => 0, 'credit' => $total, 'memo' => 'إيراد بيع #'.$sale->invoiceNumber],
        ];
        if ($paid > 0) {
            $lines[] = ['account_code' => '1010', 'debit' => $paid, 'credit' => 0, 'memo' => 'تحصيل نقدي'];
        }
        if ($due > 0) {
            $lines[] = ['account_code' => '1040', 'debit' => $due, 'credit' => 0, 'memo' => 'مبيعات آجلة'];
        }
        if ($cost > 0) {
            $lines[] = ['account_code' => '5010', 'debit' => $cost, 'credit' => 0, 'memo' => 'تكلفة المبيعات'];
            $lines[] = ['account_code' => '1030', 'debit' => 0, 'credit' => $cost, 'memo' => 'صرف مخزون'];
        }

        return $this->post($businessId, $sale->saleDate ?? now()->toDateString(), 'sale', $sale->id, $total, $lines, 'قيد بيع #'.$sale->invoiceNumber);
    }

    public function postExpense(Expense $expense): JournalEntry
    {
        $businessId = (int) $expense->business_id;
        $this->ensureChart($businessId);
        $amount = (float) $expense->amount;

        $lines = [
            ['account_code' => '5020', 'debit' => $amount, 'credit' => 0, 'memo' => 'مصروف'],
            ['account_code' => '1010', 'debit' => 0, 'credit' => $amount, 'memo' => 'دفع نقدي'],
        ];

        return $this->post($businessId, now()->toDateString(), 'expense', $expense->id, $amount, $lines, 'قيد مصروف');
    }

    public function post(int $businessId, string $date, string $source, ?int $sourceId, float $amount, array $lines, ?string $description = null): JournalEntry
    {
        return JournalEntry::create([
            'business_id' => $businessId,
            'entry_date' => $date,
            'description' => $description,
            'source' => $source,
            'source_id' => $sourceId,
            'amount' => $amount,
            'lines' => $lines,
            'user_id' => auth()->id(),
        ]);
    }

    /** Monthly VAT report: output VAT from sales journal, input from purchases. */
    public function vatReport(int $businessId, string $from, string $to): array
    {
        $entries = JournalEntry::forBusiness($businessId)
            ->whereBetween('entry_date', [$from, $to])->get();

        // VAT here is approximated at the statutory 14% on revenue lines, and
        // on purchase cost lines — replace with tax_id-linked rates when the
        // tenant completes the tax profile.
        $revenue = (float) $entries->filter(fn ($e) => $e->source === 'sale')->sum('amount');
        $purchases = (float) $entries->filter(fn ($e) => $e->source === 'purchase')->sum('amount');

        return [
            'from' => $from, 'to' => $to,
            'sales_revenue' => round($revenue, 2),
            'output_vat' => round($revenue - $revenue / 1.14, 2),
            'purchases_total' => round($purchases, 2),
            'input_vat' => round($purchases - $purchases / 1.14, 2),
            'net_vat_due' => round(($revenue - $revenue / 1.14) - ($purchases - $purchases / 1.14), 2),
        ];
    }

    /** Trial balance grouped by account type. */
    public function trialBalance(int $businessId): array
    {
        $this->ensureChart($businessId);
        $entries = JournalEntry::forBusiness($businessId)->get();
        $lines = $entries->flatMap->lines;

        $byAccount = $lines->groupBy('account_code')->map(function ($group) {
            return [
                'debit' => round((float) $group->sum('debit'), 2),
                'credit' => round((float) $group->sum('credit'), 2),
            ];
        });

        return Account::forBusiness($businessId)->get()->map(function ($account) use ($byAccount) {
            $sums = $byAccount->get($account->code, ['debit' => 0, 'credit' => 0]);

            return [
                'code' => $account->code, 'name' => $account->name, 'type' => $account->type,
                'debit' => $sums['debit'], 'credit' => $sums['credit'],
                'balance' => round($sums['debit'] - $sums['credit'], 2),
            ];
        })->toArray();
    }
}
