<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Business;
use App\Models\GeneralLedger;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('accounting')]
#[Group('new-features')]
class AccountingApiTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $user;
    private Account $cashAccount;
    private Account $revenueAccount;
    private Account $expenseAccount;
    private Account $receivableAccount;
    private Account $payableAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed account types
        AccountType::seed();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);

        // Seed chart of accounts for this business
        Account::seedForBusiness($this->business->id);

        // Grab the seeded accounts we need for tests
        $this->cashAccount = Account::where('business_id', $this->business->id)
            ->where('code', '1000')->first();
        $this->receivableAccount = Account::where('business_id', $this->business->id)
            ->where('code', '1100')->first();
        $this->payableAccount = Account::where('business_id', $this->business->id)
            ->where('code', '2000')->first();
        $this->revenueAccount = Account::where('business_id', $this->business->id)
            ->where('code', '4000')->first();
        $this->expenseAccount = Account::where('business_id', $this->business->id)
            ->where('code', '5000')->first();
    }

    // ────────────────────────────────────────
    //  Authentication
    // ────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_accounting(): void
    {
        $response = $this->getJson('/api/v1/accounting/accounts');
        $response->assertStatus(401);
    }

    // ────────────────────────────────────────
    //  Accounts
    // ────────────────────────────────────────

    public function test_can_list_accounts(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounting/accounts');

        $response->assertOk()
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_can_create_account(): void
    {
        $typeId = AccountType::where('name', AccountType::ASSET)->value('id');

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/accounting/accounts', [
                'account_type_id' => $typeId,
                'code' => '1999',
                'name' => 'Test Asset Account',
                'description' => 'A test asset account',
                'opening_balance' => 500.00,
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('accounts', [
            'business_id' => $this->business->id,
            'code' => '1999',
            'name' => 'Test Asset Account',
        ]);
    }

    public function test_can_show_account(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/accounting/accounts/{$this->cashAccount->id}");

        $response->assertOk()
            ->assertJsonStructure(['message', 'data']);
    }

    // ────────────────────────────────────────
    //  Journal Entries – CRUD
    // ────────────────────────────────────────

    public function test_can_list_journal_entries(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounting/journal-entries');

        $response->assertOk()
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_can_create_balanced_journal_entry(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/accounting/journal-entries', [
                'description' => 'Cash sale revenue',
                'entry_date' => now()->toDateString(),
                'lines' => [
                    ['account_id' => $this->cashAccount->id, 'debit' => 1000, 'credit' => 0],
                    ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 1000],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('journal_entries', [
            'business_id' => $this->business->id,
            'description' => 'Cash sale revenue',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('journal_entry_lines', [
            'account_id' => $this->cashAccount->id,
            'debit' => 1000.00,
            'credit' => 0.00,
        ]);
    }

    public function test_cannot_create_unbalanced_journal_entry(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/accounting/journal-entries', [
                'description' => 'Unbalanced entry',
                'entry_date' => now()->toDateString(),
                'lines' => [
                    ['account_id' => $this->cashAccount->id, 'debit' => 1000, 'credit' => 0],
                    ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_journal_entry_requires_minimum_two_lines(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/accounting/journal-entries', [
                'description' => 'Single line entry',
                'entry_date' => now()->toDateString(),
                'lines' => [
                    ['account_id' => $this->cashAccount->id, 'debit' => 1000, 'credit' => 0],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_can_show_journal_entry(): void
    {
        $entry = $this->createPostedEntry(500, 500);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/accounting/journal-entries/{$entry->id}");

        $response->assertOk()
            ->assertJsonStructure(['message', 'data']);
    }

    // ────────────────────────────────────────
    //  Journal Entries – Post
    // ────────────────────────────────────────

    public function test_can_post_draft_journal_entry(): void
    {
        $entry = JournalEntry::create([
            'business_id' => $this->business->id,
            'entry_number' => JournalEntry::generateEntryNumber($this->business->id),
            'entry_date' => now()->toDateString(),
            'description' => 'Test posting',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $entry->lines()->create([
            'account_id' => $this->cashAccount->id,
            'debit' => 250,
            'credit' => 0,
        ]);
        $entry->lines()->create([
            'account_id' => $this->revenueAccount->id,
            'debit' => 0,
            'credit' => 250,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/accounting/journal-entries/{$entry->id}/post");

        $response->assertOk()
            ->assertJsonStructure(['message', 'data']);

        $entry->refresh();
        $this->assertEquals('posted', $entry->status);
        $this->assertNotNull($entry->posted_at);

        // General ledger should be updated
        $this->assertDatabaseHas('general_ledger', [
            'journal_entry_id' => $entry->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 250.00,
        ]);
    }

    public function test_cannot_post_already_posted_entry(): void
    {
        $entry = $this->createPostedEntry(100, 100);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/accounting/journal-entries/{$entry->id}/post");

        $response->assertStatus(422);
    }

    // ────────────────────────────────────────
    //  Journal Entries – Void
    // ────────────────────────────────────────

    public function test_can_void_posted_journal_entry(): void
    {
        $entry = $this->createPostedEntry(300, 300);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/accounting/journal-entries/{$entry->id}/void", [
                'reason' => 'Test void',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'data']);

        $entry->refresh();
        $this->assertEquals('voided', $entry->status);

        // A reversal entry should be created
        $reversal = JournalEntry::where('description', 'like', 'VOID:%')
            ->where('business_id', $this->business->id)
            ->first();
        $this->assertNotNull($reversal);
        $this->assertEquals('posted', $reversal->status);
    }

    public function test_cannot_void_draft_entry(): void
    {
        $entry = JournalEntry::create([
            'business_id' => $this->business->id,
            'entry_number' => JournalEntry::generateEntryNumber($this->business->id),
            'entry_date' => now()->toDateString(),
            'description' => 'Draft entry',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $entry->lines()->create(['account_id' => $this->cashAccount->id, 'debit' => 100, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 100]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/accounting/journal-entries/{$entry->id}/void");

        $response->assertStatus(422);
    }

    // ────────────────────────────────────────
    //  Trial Balance
    // ────────────────────────────────────────

    public function test_can_get_trial_balance(): void
    {
        // Create a posted entry so there's something in the trial balance
        $this->createPostedEntry(1000, 1000);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounting/trial-balance');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'accounts',
                    'total_debit',
                    'total_credit',
                    'is_balanced',
                    'as_of_date',
                ],
            ]);
    }

    public function test_trial_balance_is_balanced(): void
    {
        $this->createPostedEntry(500, 500);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounting/trial-balance');

        $data = $response->json('data');
        $this->assertTrue($data['is_balanced']);
        $this->assertEquals($data['total_debit'], $data['total_credit']);
    }

    public function test_trial_balance_with_date_filter(): void
    {
        $this->createPostedEntry(200, 200);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounting/trial-balance?as_of_date=' . now()->addDay()->toDateString());

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    // ────────────────────────────────────────
    //  Income Statement
    // ────────────────────────────────────────

    public function test_can_get_income_statement(): void
    {
        // Post a revenue entry
        $this->createPostedEntry(1000, 1000);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounting/income-statement?from=' . now()->startOfYear()->toDateString() . '&to=' . now()->toDateString());

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'period',
                    'revenue',
                    'total_revenue',
                    'expenses',
                    'total_expenses',
                    'net_income',
                ],
            ]);
    }

    public function test_income_statement_requires_date_range(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounting/income-statement');

        $response->assertStatus(422);
    }

    public function test_income_statement_to_date_must_be_after_from_date(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounting/income-statement?from=2026-12-31&to=2026-01-01');

        $response->assertStatus(422);
    }

    // ────────────────────────────────────────
    //  Balance Sheet
    // ────────────────────────────────────────

    public function test_can_get_balance_sheet(): void
    {
        $this->createPostedEntry(500, 500);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/accounting/balance-sheet');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'as_of_date',
                    'assets',
                    'total_assets',
                    'liabilities',
                    'total_liabilities',
                    'equity',
                    'total_equity',
                    'is_balanced',
                ],
            ]);
    }

    // ────────────────────────────────────────
    //  General Ledger
    // ────────────────────────────────────────

    public function test_can_get_general_ledger(): void
    {
        $this->createPostedEntry(250, 250);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/accounting/general-ledger/{$this->cashAccount->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'account',
                    'opening_balance',
                    'entries',
                    'closing_balance',
                ],
            ]);
    }

    // ────────────────────────────────────────
    //  Tenant Isolation
    // ────────────────────────────────────────

    public function test_user_cannot_see_other_business_accounts(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/accounting/accounts/{$this->cashAccount->id}");

        $response->assertStatus(404);
    }

    public function test_user_cannot_access_other_business_journal_entries(): void
    {
        $entry = $this->createPostedEntry(100, 100);

        $otherBusiness = Business::factory()->create();
        $otherUser = User::factory()->create(['business_id' => $otherBusiness->id]);

        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/accounting/journal-entries/{$entry->id}");

        $response->assertStatus(404);
    }

    // ────────────────────────────────────────
    //  Multiple Entries – Reconciliation Test
    // ────────────────────────────────────────

    public function test_multiple_posted_entries_update_general_ledger_correctly(): void
    {
        // Entry 1: Debit Cash 1000, Credit Revenue 1000
        $entry1 = $this->createPostedEntry(1000, 1000);

        // Entry 2: Debit Expense 500, Credit Cash 500
        $entry2 = JournalEntry::create([
            'business_id' => $this->business->id,
            'entry_number' => JournalEntry::generateEntryNumber($this->business->id),
            'entry_date' => now()->toDateString(),
            'description' => 'Payment for expenses',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $entry2->lines()->create(['account_id' => $this->expenseAccount->id, 'debit' => 500, 'credit' => 0]);
        $entry2->lines()->create(['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 500]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/accounting/journal-entries/{$entry2->id}/post")
            ->assertOk();

        // Cash should have a net balance of 500 (1000 debit - 500 credit)
        $glEntries = GeneralLedger::where('business_id', $this->business->id)
            ->where('account_id', $this->cashAccount->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $glEntries);
        $this->assertEquals(1000.00, $glEntries->first()->balance);

        $lastGl = $glEntries->last();
        // Cash is debit-positive: previous balance (1000) + 0 debit - 500 credit = 500
        $this->assertEquals(500.00, $lastGl->balance);
    }

    // ────────────────────────────────────────
    //  Entry Number Generation
    // ────────────────────────────────────────

    public function test_entry_numbers_are_sequential(): void
    {
        $num1 = JournalEntry::generateEntryNumber($this->business->id);
        JournalEntry::create([
            'business_id' => $this->business->id,
            'entry_number' => $num1,
            'entry_date' => now()->toDateString(),
            'description' => 'First entry',
            'status' => 'draft',
        ]);

        $num2 = JournalEntry::generateEntryNumber($this->business->id);

        // Both should have the same date prefix
        $prefix = 'JE-' . now()->format('Ymd') . '-';
        $this->assertStringStartsWith($prefix, $num1);
        $this->assertStringStartsWith($prefix, $num2);

        // Second should be sequentially higher
        $seq1 = (int) substr($num1, -4);
        $seq2 = (int) substr($num2, -4);
        $this->assertGreaterThan($seq1, $seq2);
    }

    // ────────────────────────────────────────
    //  Business isBalanced check
    // ────────────────────────────────────────

    public function test_journal_entry_is_balanced_returns_true_for_balanced_entry(): void
    {
        $entry = JournalEntry::create([
            'business_id' => $this->business->id,
            'entry_number' => 'TEST-001',
            'entry_date' => now()->toDateString(),
            'description' => 'Balanced test',
            'status' => 'draft',
        ]);

        $entry->lines()->create(['account_id' => $this->cashAccount->id, 'debit' => 200, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 200]);

        $this->assertTrue($entry->isBalanced());
    }

    public function test_journal_entry_is_balanced_returns_false_for_unbalanced_entry(): void
    {
        $entry = JournalEntry::create([
            'business_id' => $this->business->id,
            'entry_number' => 'TEST-002',
            'entry_date' => now()->toDateString(),
            'description' => 'Unbalanced test',
            'status' => 'draft',
        ]);

        $entry->lines()->create(['account_id' => $this->cashAccount->id, 'debit' => 300, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 100]);

        $this->assertFalse($entry->isBalanced());
    }

    // ────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────

    private function createPostedEntry(float $debit, float $credit): JournalEntry
    {
        $entry = JournalEntry::create([
            'business_id' => $this->business->id,
            'entry_number' => JournalEntry::generateEntryNumber($this->business->id),
            'entry_date' => now()->toDateString(),
            'description' => 'Test entry',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $entry->lines()->create(['account_id' => $this->cashAccount->id, 'debit' => $debit, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => $credit]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/accounting/journal-entries/{$entry->id}/post");

        return $entry->fresh();
    }
}
