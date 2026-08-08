<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTransaction;
use App\Models\Party;
use App\Models\Sale;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoyaltyServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Party $party;

    private LoyaltyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->user = User::factory()->create(['business_id' => $this->business->id]);
        $this->party = Party::factory()->create(['business_id' => $this->business->id]);
        $this->service = new LoyaltyService;

        Auth::login($this->user);
    }

    public function test_get_or_create_program_creates_default(): void
    {
        $program = $this->service->getOrCreateProgram($this->business->id);

        $this->assertInstanceOf(LoyaltyProgram::class, $program);
        $this->assertEquals('Default Loyalty Program', $program->name);
        $this->assertEquals(1, $program->points_per_currency);
        $this->assertTrue($program->is_active);
    }

    public function test_get_or_create_program_returns_existing(): void
    {
        LoyaltyProgram::create([
            'business_id' => $this->business->id,
            'name' => 'Custom Program',
            'points_per_currency' => 2,
            'min_points_for_reward' => 200,
            'is_active' => true,
        ]);

        $program = $this->service->getOrCreateProgram($this->business->id);

        $this->assertEquals('Custom Program', $program->name);
        $this->assertEquals(2, $program->points_per_currency);
    }

    public function test_earn_points_creates_transaction(): void
    {
        $transaction = $this->service->earnPoints($this->business->id, $this->party->id, 100.0);

        $this->assertInstanceOf(LoyaltyTransaction::class, $transaction);
        $this->assertEquals('earned', $transaction->type);
        $this->assertEquals(100, $transaction->points);
    }

    public function test_earn_points_does_not_create_when_program_inactive(): void
    {
        $this->service->getOrCreateProgram($this->business->id);
        LoyaltyProgram::where('business_id', $this->business->id)->update(['is_active' => false]);

        $transaction = $this->service->earnPoints($this->business->id, $this->party->id, 100.0);

        $this->assertNull($transaction);
    }

    public function test_earn_points_with_custom_rate(): void
    {
        $program = $this->service->getOrCreateProgram($this->business->id);
        $program->update(['points_per_currency' => 5]);

        $transaction = $this->service->earnPoints($this->business->id, $this->party->id, 100.0);

        $this->assertEquals(500, $transaction->points);
    }

    public function test_redeem_points_creates_negative_transaction(): void
    {
        $this->service->earnPoints($this->business->id, $this->party->id, 200.0);

        $transaction = $this->service->redeemPoints($this->business->id, $this->party->id, 50);

        $this->assertEquals('redeemed', $transaction->type);
        $this->assertEquals(-50, $transaction->points);
    }

    public function test_redeem_points_throws_on_insufficient_balance(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient loyalty points.');

        $this->service->redeemPoints($this->business->id, $this->party->id, 100);
    }

    public function test_redeem_points_throws_on_zero_points(): void
    {
        $this->service->earnPoints($this->business->id, $this->party->id, 100.0);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Points must be greater than zero.');

        $this->service->redeemPoints($this->business->id, $this->party->id, 0);
    }

    public function test_get_balance_returns_zero_for_no_transactions(): void
    {
        $balance = $this->service->getBalance($this->business->id, $this->party->id);

        $this->assertEquals(0, $balance);
    }

    public function test_get_balance_returns_sum_of_transactions(): void
    {
        $this->service->earnPoints($this->business->id, $this->party->id, 100.0);
        $this->service->earnPoints($this->business->id, $this->party->id, 50.0);
        $this->service->redeemPoints($this->business->id, $this->party->id, 30);

        $balance = $this->service->getBalance($this->business->id, $this->party->id);

        $this->assertEquals(120, $balance);
    }

    public function test_get_history_returns_transactions(): void
    {
        $this->service->earnPoints($this->business->id, $this->party->id, 100.0);
        $this->service->redeemPoints($this->business->id, $this->party->id, 50);

        $history = $this->service->getHistory($this->business->id, $this->party->id);

        $this->assertCount(2, $history);
    }

    public function test_log_interaction_creates_record(): void
    {
        $this->service->logInteraction(
            $this->business->id,
            $this->party->id,
            'call',
            'Discussed medication refill'
        );

        $this->assertDatabaseHas('customer_interactions', [
            'business_id' => $this->business->id,
            'party_id' => $this->party->id,
            'type' => 'call',
        ]);
    }

    public function test_earn_points_with_sale_reference(): void
    {
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'party_id' => $this->party->id,
            'user_id' => $this->user->id,
            'totalAmount' => 500.0,
            'paidAmount' => 500.0,
            'dueAmount' => 0.0,
            'isPaid' => true,
            'paymentType' => 'cash',
            'saleDate' => now()->toDateString(),
        ]);

        $transaction = $this->service->earnPoints($this->business->id, $this->party->id, 100.0, $sale);

        $this->assertNotNull($transaction->reference_id);
        $this->assertEquals(Sale::class, $transaction->reference_type);
    }
}
