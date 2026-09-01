<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'business_id', 'account_type_id', 'code', 'name',
        'description', 'is_system', 'is_active', 'opening_balance',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function generalLedgerEntries(): HasMany
    {
        return $this->hasMany(GeneralLedger::class);
    }

    /**
     * Calculate the current balance from general ledger entries.
     */
    public function getCurrentBalance(): string
    {
        $totals = $this->generalLedgerEntries()
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $debit = (float) ($totals->total_debit ?? 0);
        $credit = (float) ($totals->total_credit ?? 0);
        $opening = (float) $this->opening_balance;

        if ($this->accountType->is_debit_positive) {
            return number_format($opening + $debit - $credit, 2, '.', '');
        }

        return number_format($opening + $credit - $debit, 2, '.', '');
    }

    /**
     * Check if this is a debit-positive account type (Asset or Expense).
     */
    public function isDebitPositive(): bool
    {
        return $this->accountType->is_debit_positive;
    }

    /**
     * Seed default chart of accounts for a business.
     */
    public static function seedForBusiness(int $businessId): void
    {
        // Ensure account types exist
        AccountType::seed();
        $types = AccountType::pluck('id', 'name')->toArray();

        $accounts = [
            // Assets (1xxx)
            ['code' => '1000', 'name' => 'Cash',                    'type' => AccountType::ASSET,     'system' => true],
            ['code' => '1010', 'name' => 'Petty Cash',              'type' => AccountType::ASSET,     'system' => true],
            ['code' => '1100', 'name' => 'Accounts Receivable',     'type' => AccountType::ASSET,     'system' => true],
            ['code' => '1200', 'name' => 'Inventory',               'type' => AccountType::ASSET,     'system' => true],
            ['code' => '1300', 'name' => 'Prepaid Expenses',        'type' => AccountType::ASSET,     'system' => false],
            // Liabilities (2xxx)
            ['code' => '2000', 'name' => 'Accounts Payable',        'type' => AccountType::LIABILITY, 'system' => true],
            ['code' => '2100', 'name' => 'Sales Tax Payable',       'type' => AccountType::LIABILITY, 'system' => true],
            ['code' => '2200', 'name' => 'Accrued Expenses',        'type' => AccountType::LIABILITY, 'system' => false],
            // Equity (3xxx)
            ['code' => '3000', 'name' => "Owner's Equity",          'type' => AccountType::EQUITY,    'system' => true],
            ['code' => '3100', 'name' => 'Retained Earnings',       'type' => AccountType::EQUITY,    'system' => true],
            ['code' => '3200', 'name' => 'Current Year Earnings',   'type' => AccountType::EQUITY,    'system' => true],
            // Revenue (4xxx)
            ['code' => '4000', 'name' => 'Sales Revenue',           'type' => AccountType::REVENUE,   'system' => true],
            ['code' => '4100', 'name' => 'Sales Returns',           'type' => AccountType::REVENUE,   'system' => true],
            ['code' => '4200', 'name' => 'Discount Revenue',        'type' => AccountType::REVENUE,   'system' => false],
            // Expenses (5xxx)
            ['code' => '5000', 'name' => 'Cost of Goods Sold',      'type' => AccountType::EXPENSE,   'system' => true],
            ['code' => '5100', 'name' => 'Purchase Returns',        'type' => AccountType::EXPENSE,   'system' => true],
            ['code' => '5200', 'name' => 'Salary Expense',          'type' => AccountType::EXPENSE,   'system' => false],
            ['code' => '5300', 'name' => 'Rent Expense',            'type' => AccountType::EXPENSE,   'system' => false],
            ['code' => '5400', 'name' => 'Utilities Expense',       'type' => AccountType::EXPENSE,   'system' => false],
            ['code' => '5500', 'name' => 'Discount Allowed',        'type' => AccountType::EXPENSE,   'system' => false],
        ];

        foreach ($accounts as $account) {
            static::updateOrCreate(
                ['business_id' => $businessId, 'code' => $account['code']],
                [
                    'account_type_id' => $types[$account['type']],
                    'name' => $account['name'],
                    'is_system' => $account['system'],
                ]
            );
        }
    }
}
