<?php

namespace App\Services;

use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTransaction;
use App\Models\CustomerInteraction;
use App\Models\Party;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    public function getOrCreateProgram(int $businessId): LoyaltyProgram
    {
        return LoyaltyProgram::firstOrCreate(
            ['business_id' => $businessId],
            ['name' => 'Default Loyalty Program', 'points_per_currency' => 1, 'min_points_for_reward' => 100, 'is_active' => true]
        );
    }

    private function resolveProgram(int $businessOrProgramId): ?LoyaltyProgram
    {
        return LoyaltyProgram::find($businessOrProgramId) ?? $this->getOrCreateProgram($businessOrProgramId);
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

    public function getBalance(int $businessId, int $partyId): int
    {
        $program = $this->resolveProgram($businessId);
        return $program ? $this->getCustomerBalance($partyId, $program->id) : 0;
    }

    public function getHistory(int $businessId, int $partyId): array
    {
        $program = $this->resolveProgram($businessId);
        return $program ? LoyaltyTransaction::where('loyalty_program_id', $program->id)->where('party_id', $partyId)->latest()->get()->all() : [];
    }

    public function logInteraction(int $businessId, int $partyId, string $type, ?string $notes = null): CustomerInteraction
    {
        return $this->createInteraction(['business_id' => $businessId, 'party_id' => $partyId, 'type' => $type, 'notes' => $notes, 'user_id' => auth()->id()]);
    }

    /**
     * Earn points for a customer
     */
    public function earnPoints(int $businessOrProgramId, int $partyId, float $amount, $reference = null, ?int $referenceId = null): ?LoyaltyTransaction
    {
        return DB::transaction(function () use ($businessOrProgramId, $partyId, $amount, $reference, $referenceId) {
            $program = $this->resolveProgram($businessOrProgramId);
            if (! $program || ! $program->is_active) {
                return null;
            }
            $points = $program->calculatePoints($amount);
            $referenceType = is_object($reference) ? $reference::class : $reference;
            $resolvedReferenceId = is_object($reference) ? $reference->getKey() : $referenceId;

            return LoyaltyTransaction::create([
                'business_id' => $program->business_id,
                'loyalty_program_id' => $program->id,
                'party_id' => $partyId,
                'points' => $points,
                'type' => 'earned',
                'reference_type' => $referenceType,
                'reference_id' => $resolvedReferenceId,
                'notes' => "Earned {$points} points from purchase of {$amount}",
            ]);
        });
    }

    /**
     * Redeem points for a customer
     */
    public function redeemPoints(int $businessOrProgramId, int $partyId, int $points, ?string $notes = null): LoyaltyTransaction
    {
        return DB::transaction(function () use ($businessOrProgramId, $partyId, $points, $notes) {
            if ($points <= 0) {
                throw new \Exception('Points must be greater than zero.');
            }
            $program = $this->resolveProgram($businessOrProgramId);
            $currentBalance = $program ? $this->getCustomerBalance($partyId, $program->id) : 0;

            if ($currentBalance < $points) {
                throw new \Exception('Insufficient loyalty points.');
            }


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
