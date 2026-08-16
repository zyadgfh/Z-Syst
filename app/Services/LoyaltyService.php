<?php

namespace App\Services;

use App\Models\CustomerInteraction;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTransaction;
use App\Models\Party;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * Get or create default loyalty program for a business
     */
    public function getOrCreateProgram(int $businessId): LoyaltyProgram
    {
        $program = LoyaltyProgram::forBusiness($businessId)->first();

        if (! $program) {
            $program = $this->createProgram([
                'business_id' => $businessId,
                'name' => 'Default Loyalty Program',
                'description' => 'Default loyalty program for the business',
                'points_per_currency' => 1,
                'min_points_to_redeem' => 100,
                'is_active' => true,
            ]);
        }

        return $program;
    }

    /**
     * Get the active loyalty program for a business
     */
    protected function getActiveProgram(int $businessId): LoyaltyProgram
    {
        $program = LoyaltyProgram::forBusiness($businessId)->where('is_active', true)->first();

        if (! $program) {
            $program = $this->getOrCreateProgram($businessId);
        }

        return $program;
    }

    /**
     * Create loyalty program
     */
    public function createProgram(array $data): LoyaltyProgram
    {
        return LoyaltyProgram::create($data);
    }

    /**
     * Update loyalty program
     */
    public function updateProgram(LoyaltyProgram $program, array $data): LoyaltyProgram
    {
        $program->update($data);

        return $program->fresh();
    }

    /**
     * Earn points for a customer (by business_id)
     */
    public function earnPoints(int $businessId, int $partyId, float $amount, ?string $referenceType = null, ?int $referenceId = null): ?LoyaltyTransaction
    {
        $program = $this->getActiveProgram($businessId);

        if (! $program->is_active) {
            return null;
        }

        return DB::transaction(function () use ($program, $partyId, $amount, $referenceType, $referenceId) {
            $points = $program->calculatePoints($amount);

            return LoyaltyTransaction::create([
                'business_id' => $program->business_id,
                'loyalty_program_id' => $program->id,
                'party_id' => $partyId,
                'points' => $points,
                'type' => 'earned',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => "Earned {$points} points from purchase of {$amount}",
            ]);
        });
    }

    /**
     * Redeem points for a customer (by business_id)
     */
    public function redeemPoints(int $businessId, int $partyId, int $points, ?string $notes = null): LoyaltyTransaction
    {
        $program = $this->getActiveProgram($businessId);
        $currentBalance = $this->getBalance($businessId, $partyId);

        if ($points <= 0) {
            throw new \Exception('Points must be greater than zero.');
        }

        if ($currentBalance < $points) {
            throw new \Exception('Insufficient loyalty points.');
        }

        if (! $program->canRedeemReward($points)) {
            throw new \Exception('Minimum points requirement not met');
        }

        return DB::transaction(function () use ($program, $partyId, $points, $notes) {
            return LoyaltyTransaction::create([
                'business_id' => $program->business_id,
                'loyalty_program_id' => $program->id,
                'party_id' => $partyId,
                'points' => -$points, // Negative for redemption
                'type' => 'redeemed',
                'notes' => $notes ?? "Redeemed {$points} points",
            ]);
        });
    }

    /**
     * Get customer's points balance (by business_id)
     */
    public function getBalance(int $businessId, int $partyId): int
    {
        $program = $this->getActiveProgram($businessId);

        return LoyaltyTransaction::where('loyalty_program_id', $program->id)
            ->where('party_id', $partyId)
            ->sum('points');
    }

    /**
     * Get customer's transaction history (by business_id)
     */
    public function getHistory(int $businessId, int $partyId, int $limit = 50): array
    {
        $program = $this->getActiveProgram($businessId);

        $transactions = LoyaltyTransaction::where('loyalty_program_id', $program->id)
            ->where('party_id', $partyId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return [
            'current_balance' => $this->getBalance($businessId, $partyId),
            'total_earned' => LoyaltyTransaction::where('loyalty_program_id', $program->id)
                ->where('party_id', $partyId)
                ->earned()
                ->sum('points'),
            'total_redeemed' => abs(LoyaltyTransaction::where('loyalty_program_id', $program->id)
                ->where('party_id', $partyId)
                ->redeemed()
                ->sum('points')),
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'points' => $transaction->points,
                    'type' => $transaction->type,
                    'type_label' => $transaction->type_label,
                    'notes' => $transaction->notes,
                    'created_at' => $transaction->created_at->toIso8601String(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get loyalty program statistics
     */
    public function getProgramStatistics(int $businessId, ?int $programId = null): array
    {
        $query = LoyaltyProgram::forBusiness($businessId);

        if ($programId) {
            $query->where('id', $programId);
        }

        $programs = $query->get();

        $totalMembers = 0;
        $totalPointsIssued = 0;
        $totalPointsRedeemed = 0;

        foreach ($programs as $program) {
            $totalMembers += LoyaltyTransaction::where('loyalty_program_id', $program->id)
                ->distinct('party_id')
                ->count('party_id');

            $totalPointsIssued += LoyaltyTransaction::where('loyalty_program_id', $program->id)
                ->earned()
                ->sum('points');

            $totalPointsRedeemed += abs(LoyaltyTransaction::where('loyalty_program_id', $program->id)
                ->redeemed()
                ->sum('points'));
        }

        return [
            'total_programs' => $programs->count(),
            'active_programs' => $programs->where('is_active', true)->count(),
            'total_members' => $totalMembers,
            'total_points_issued' => $totalPointsIssued,
            'total_points_redeemed' => $totalPointsRedeemed,
            'redemption_rate' => $totalPointsIssued > 0
                ? round(($totalPointsRedeemed / $totalPointsIssued) * 100, 2)
                : 0,
        ];
    }

    /**
     * Create customer interaction
     */
    public function createInteraction(array $data): CustomerInteraction
    {
        return CustomerInteraction::create($data);
    }

    /**
     * Log customer interaction (alias for createInteraction with specific parameters)
     */
    public function logInteraction(int $businessId, int $partyId, string $type, string $notes): CustomerInteraction
    {
        return $this->createInteraction([
            'business_id' => $businessId,
            'party_id' => $partyId,
            'type' => $type,
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Get customer interaction history
     */
    public function getCustomerInteractions(int $partyId, int $businessId, int $limit = 50): array
    {
        $interactions = CustomerInteraction::forBusiness($businessId)
            ->forParty($partyId)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return [
            'total_interactions' => $interactions->count(),
            'interactions_by_type' => [
                'calls' => $interactions->where('type', 'call')->count(),
                'visits' => $interactions->where('type', 'visit')->count(),
                'emails' => $interactions->where('type', 'email')->count(),
                'meetings' => $interactions->where('type', 'meeting')->count(),
            ],
            'interactions' => $interactions->map(function ($interaction) {
                return [
                    'id' => $interaction->id,
                    'type' => $interaction->type,
                    'type_label' => $interaction->type_label,
                    'notes' => $interaction->notes,
                    'user' => $interaction->user?->name,
                    'created_at' => $interaction->created_at->toIso8601String(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get CRM statistics
     */
    public function getCrmStatistics(int $businessId, array $filters = []): array
    {
        $query = CustomerInteraction::forBusiness($businessId);

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $interactions = $query->get();

        return [
            'total_interactions' => $interactions->count(),
            'interactions_by_type' => [
                'calls' => $interactions->where('type', 'call')->count(),
                'visits' => $interactions->where('type', 'visit')->count(),
                'emails' => $interactions->where('type', 'email')->count(),
                'meetings' => $interactions->where('type', 'meeting')->count(),
                'support' => $interactions->where('type', 'support')->count(),
            ],
            'unique_customers_contacted' => $interactions->unique('party_id')->count(),
        ];
    }

    /**
     * Get top loyal customers
     */
    public function getTopLoyalCustomers(int $businessId, int $programId, int $limit = 10): array
    {
        $customerBalances = LoyaltyTransaction::where('loyalty_program_id', $programId)
            ->selectRaw('party_id, SUM(points) as balance')
            ->groupBy('party_id')
            ->orderByDesc('balance')
            ->limit($limit)
            ->get();

        return $customerBalances->map(function ($item) {
            $party = Party::find($item->party_id);

            return [
                'party_id' => $item->party_id,
                'party_name' => $party?->name ?? 'Unknown',
                'balance' => $item->balance,
            ];
        })->toArray();
    }
}
