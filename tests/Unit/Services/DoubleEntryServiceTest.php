<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Business;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\DoubleEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoubleEntryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DoubleEntryService $service;
    protected User $user;
    protected Business $business;
    protected int $assetTypeId;
    protected int $revenueTypeId;
    protected int $expenseTypeId;
    protected int $cashAccountId;
    protected int $salesAccountId;
    protected int $cogsAccountId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);

        AccountType::seed();

        $this->assetTypeId = AccountType::where('name', AccountType::ASSET)->first()->id;
        $this->revenueTypeId = AccountType::where('name', AccountType::REVENUE)->first()->id;
        $this->expenseTypeId = AccountType::where('name', AccountType::EXPENSE)->first()->id;

        $this->cashAccountId = Account::create([
            'business_id' => $this->business->id,
            'account_type_id' => $this->assetTypeId,
            'code' => '1000',
            'name' => 'Cash',
            'is_system' => true,
        ])->id;

        $this->salesAccountId = Account::create([
            'business_id' => $this->business->id,
            'account_type_id' => $this->revenueTypeId,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'is_system' => true,
        ])->id;

        $this->cogsAccountId = Account::create([
            'business_id' => $this->business->id,
            'account_type_id' => $this->expenseTypeId,
            'code' => '5000',
            'name' => 'Cost of Goods Sold',
            'is_system' => true,
        ])->id;

        $this->service = new DoubleEntryService();
    }

    public function test_create_journal_entry_creates_entry_with_lines(): void
    {
        $entry = $this->service->createJournalEntry(
            businessId: $this->business->id,
            description: 'Test journal entry',
            lines: [
                ['account_id' => $this->cashAccountId, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->salesAccountId, 'debit' => 0, 'credit' => 100],
            ],
            userId: $this->user->id,
        );

        $this->assertNotNull($entry);
        $this->assertEquals('draft', $entry->status);
        $this->assertEquals(2, $entry->lines->count());
        $this->assertStringStartsWith('JE-', $entry->entry_number);
    }

    public function test_create_journal_entry_throws_when_unbalanced(): void
    {
        $this->expectException(\App\Exceptions\BusinessRuleException::class);
        $this->expectExceptionCode(0);

        $this->service->createJournalEntry(
            businessId: $this->business->id,
            description: 'Unbalanced entry',
            lines: [
                ['account_id' => $this->cashAccountId, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->salesAccountId, 'debit' => 0, 'credit' => 50],
            ],
        );
    }

    public function test_post_journal_entry_updates_status(): void
    {
        $entry = $this->service->createJournalEntry(
            businessId: $this->business->id,
            description: 'Sale recorded',
            lines: [
                ['account_id' => $this->cashAccountId, 'debit' => 150, 'credit' => 0],
                ['account_id' => $this->salesAccountId, 'debit' => 0, 'credit' => 150],
            ],
            userId: $this->user->id,
        );

        $posted = $this->service->postJournalEntry($entry, $this->user->id);

        $this->assertEquals('posted', $posted->status);
        $this->assertNotNull($posted->posted_at);
        $this->assertEquals($this->user->id, $posted->posted_by);
    }

    public function test_post_journal_entry_creates_general_ledger_entries(): void
    {
        $entry = $this->service->createJournalEntry(
            businessId: $this->business->id,
            description: 'Sale',
            lines: [
                ['account_id' => $this->cashAccountId, 'debit' => 200, 'credit' => 0],
                ['account_id' => $this->salesAccountId, 'debit' => 0, 'credit' => 200],
            ],
            userId: $this->user->id,
        );

        $this->service->postJournalEntry($entry, $this->user->id);

        $glEntries = \App\Models\GeneralLedger::where('journal_entry_id', $entry->id)->get();
        $this->assertCount(2, $glEntries);

        $cashGl = $glEntries->firstWhere('account_id', $this->cashAccountId);
        $this->assertEquals(200, $cashGl->debit);
        $this->assertEquals(0, $cashGl->credit);
        $this->assertEquals(200, $cashGl->balance);
    }

    public function test_post_journal_entry_throws_when_already_posted(): void
    {
        $entry = $this->service->createJournalEntry(
            businessId: $this->business->id,
            description: 'Already posted',
            lines: [
                ['account_id' => $this->cashAccountId, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->salesAccountId, 'debit' => 0, 'credit' => 100],
            ],
        );
        $this->service->postJournalEntry($entry, $this->user->id);

        $this->expectException(\App\Exceptions\BusinessRuleException::class);
        $this->service->postJournalEntry($entry->fresh(), $this->user->id);
    }

    public function test_void_journal_entry_reverses_balance(): void
    {
        $entry = $this->service->createJournalEntry(
            businessId: $this->business->id,
            description: 'Sale to void',
            lines: [
                ['account_id' => $this->cashAccountId, 'debit' => 300, 'credit' => 0],
                ['account_id' => $this->salesAccountId, 'debit' => 0, 'credit' => 300],
            ],
            userId: $this->user->id,
        );
        $this->service->postJournalEntry($entry, $this->user->id);

        $reverse = $this->service->voidJournalEntry($entry, 'Mistake');

        $this->assertEquals('voided', $entry->fresh()->status);
        $this->assertEquals('posted', $reverse->status);
        $this->assertStringStartsWith('VOID:', $reverse->description);
    }

    public function test_trial_balance_is_balanced(): void
    {
        $this->service->createJournalEntry(
            businessId: $this->business->id,
            description: 'Entry 1',
            lines: [
                ['account_id' => $this->cashAccountId, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->salesAccountId, 'debit' => 0, 'credit' => 500],
            ],
        );
        // Post it
        $entry = JournalEntry::where('business_id', $this->business->id)->first();
        $this->service->postJournalEntry($entry, $this->user->id);

        $trialBalance = $this->service->getTrialBalance($this->business->id);

        $this->assertTrue($trialBalance['is_balanced']);
        $this->assertEquals($trialBalance['total_debit'], $trialBalance['total_credit']);
    }

    public function test_get_general_ledger_returns_account_entries(): void
    {
        $entry = $this->service->createJournalEntry(
            businessId: $this->business->id,
            description: 'Test GL',
            lines: [
                ['account_id' => $this->cashAccountId, 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->salesAccountId, 'debit' => 0, 'credit' => 100],
            ],
        );
        $this->service->postJournalEntry($entry, $this->user->id);

        $gl = $this->service->getGeneralLedger($this->business->id, $this->cashAccountId);

        $this->assertArrayHasKey('account', $gl);
        $this->assertArrayHasKey('entries', $gl);
        $this->assertCount(1, $gl['entries']);
        $this->assertEquals(100, $gl['entries']->first()->debit);
    }

    public function test_get_accounts_returns_all(): void
    {
        $accounts = $this->service->getAccounts($this->business->id);

        $this->assertCount(3, $accounts);
        $this->assertEquals('1000', $accounts->first()->code);
    }

    public function test_create_account(): void
    {
        $account = $this->service->createAccount($this->business->id, [
            'account_type_id' => $this->assetTypeId,
            'code' => '1300',
            'name' => 'Prepaid Expenses',
        ]);

        $this->assertEquals('Prepaid Expenses', $account->name);
        $this->assertEquals($this->business->id, $account->business_id);
    }

    public function test_income_statement_calculates_net_income(): void
    {
        // Record revenue
        $revenueEntry = $this->service->createJournalEntry(
            businessId: $this->business->id,
            description: 'Sales revenue',
            lines: [
                ['account_id' => $this->cashAccountId, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->salesAccountId, 'debit' => 0, 'credit' => 1000],
            ],
        );
        $this->service->postJournalEntry($revenueEntry, $this->user->id);

        // Record expense
        $expenseEntry = $this->service->createJournalEntry(
            businessId: $this->business->id,
            description: 'COGS',
            lines: [
                ['account_id' => $this->cogsAccountId, 'debit' => 600, 'credit' => 0],
                ['account_id' => $this->cashAccountId, 'debit' => 0, 'credit' => 600],
            ],
        );
        $this->service->postJournalEntry($expenseEntry, $this->user->id);

        $incomeStatement = $this->service->getIncomeStatement(
            $this->business->id,
            now()->startOfYear()->toDateString(),
            now()->endOfYear()->toDateString()
        );

        $this->assertEquals(1000, $incomeStatement['total_revenue']);
        $this->assertEquals(600, $incomeStatement['total_expenses']);
        $this->assertEquals(400, $incomeStatement['net_income']);
    }
}
