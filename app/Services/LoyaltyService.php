<?php

namespace App\Services;

use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTransaction;
use App\Models\CustomerInteraction;
use App\Models\Party;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
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
     * Earn points for a customer
     */
    public function earnPoints(int $programId, int $partyId, float $amount, string $referenceType = null, int $referenceId = null): LoyaltyTransaction
    {
        return DB::transaction(function () use ($programId, $partyId, $amount, $referenceType, $referenceId) {
            $program = LoyaltyProgram::findOrFail($programId);
            $points = $program->calculatePoints($amount);

            return LoyaltyTransaction::create([
                'business_id' => $program->business_id,
                'loyalty_program_id' => $programId,
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
     * Redeem points for a customer
     */
    public function redeemPoints(int $programId, int $partyId, int $points, string $notes = null): LoyaltyTransaction
    {
        return DB::transaction(function () use ($programId, $partyId, $points, $notes) {
            $program = LoyaltyProgram::findOrFail($programId);
            $currentBalance = $this->getCustomerBalance($partyId, $programId);

            if ($currentBalance < $points) {
                throw new \Exception('Insufficient points balance');
            }

            if (!$program->canRedeemReward($points)) {
                throw new \Exception('Minimum points requirement not met');
            }

            return LoyaltyTransaction::create([
                'business_id' => $program->business_id,
                'loyalty_program_id' => $programId,
                'party_id' => $partyId,
                'points' => -$points, // Negative for redemption
                'type' => 'redeemed',
                'notes' => $notes ?? "Redeemed {$points} points",
            ]);
        });
    }

    /**
     * Get customer's points balance
     */
    public function getCustomerBalance(int $partyId, int $programId): int
    {
        return LoyaltyTransaction::where('loyalty_program_id', $programId)
            ->where('party_id', $partyId)
            ->sum('points');
    }

    /**
     * Get customer's transaction history
     */
    public function getCustomerTransactions(int $partyId, int $programId, int $limit = 50): array
    {
        $transactions = LoyaltyTransaction::where('loyalty_program_id', $programId)
            ->where('party_id', $partyId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return [
            'current_balance' => $this->getCustomerBalance($partyId, $programId),
            'total_earned' => LoyaltyTransaction::where('loyalty_program_id', $programId)
                ->where('party_id', $partyId)
                ->earned()
                ->sum('points'),
            'total_redeemed' => abs(LoyaltyTransaction::where('loyalty_program_id', $programId)
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
    public function getProgramStatistics(int $businessId, int $programId = null): array
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
