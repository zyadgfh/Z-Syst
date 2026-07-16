<?php

namespace App\Services\Payment\Gateways;

use App\Models\Party;
use App\Services\Payment\Enums\PaymentMethodType;
use App\Services\Payment\Enums\TransactionStatus;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\Models\PaymentTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * BNPL (Buy Now Pay Later) Payment Gateway
 * 
 * نظام الدفع الآجل (أجل) - تقسيط المشتريات
 * يدعم: عقود التقسيط، جدولة الدفعات، تتبع المستحقات، تنبيهات الاستحقاق
 */
class BNPLPaymentGateway extends BaseGateway
{
    protected function getConfigPrefix(): string
    {
        return 'bnpl';
    }

    protected function loadConfig(): void
    {
        $this->config = [
            'min_amount' => config('services.bnpl.min_amount', 100),
            'max_amount' => config('services.bnpl.max_amount', 100000),
            'min_installments' => config('services.bnpl.min_installments', 2),
            'max_installments' => config('services.bnpl.max_installments', 24),
            'default_interest_rate' => config('services.bnpl.default_interest_rate', 0),
            'late_fee_percentage' => config('services.bnpl.late_fee_percentage', 2),
            'grace_period_days' => config('services.bnpl.grace_period_days', 3),
            'require_guarantee' => config('services.bnpl.require_guarantee', false),
        ];
    }

    public function getMethodType(): PaymentMethodType
    {
        return PaymentMethodType::BNPL;
    }

    public function getDisplayName(): string
    {
        return 'BNPL / أجل';
    }

    public function getRequiredConfig(): array
    {
        return [];
    }

    /**
     * Initiate a BNPL contract.
     * Creates the contract with installment schedule.
     */
    public function initiate(array $data): PaymentTransaction
    {
        $amount = $data['amount'];
        $this->validateAmount($amount);

        $customerId = $data['customer_id'] ?? null;
        if (!$customerId) {
            throw new PaymentException(
                'Customer is required for BNPL payment.',
                422,
                gatewayName: $this->getDisplayName()
            );
        }

        $customer = Party::find($customerId);
        if (!$customer) {
            throw new PaymentException(
                'Customer not found.',
                404,
                gatewayName: $this->getDisplayName()
            );
        }

        $installmentsCount = $data['installments_count'] ?? 3;
        $downPayment = $data['down_payment'] ?? 0;
        $frequency = $data['frequency'] ?? 'monthly';
        $interestRate = $data['interest_rate'] ?? $this->config['default_interest_rate'];

        // Validate installments
        if ($installmentsCount < $this->config['min_installments'] || 
            $installmentsCount > $this->config['max_installments']) {
            throw new PaymentException(
                "Installments must be between {$this->config['min_installments']} and {$this->config['max_installments']}.",
                422,
                gatewayName: $this->getDisplayName()
            );
        }

        // Calculate financing amount
        $financedAmount = $amount - $downPayment;
        $totalWithInterest = $financedAmount * (1 + ($interestRate / 100));
        $installmentAmount = $totalWithInterest / $installmentsCount;

        // Generate schedule dates
        $scheduleDates = $this->generateScheduleDates($installmentsCount, $frequency);

        // Create the initial payment transaction for the down payment
        $transaction = $this->createTransaction([
            'amount' => $downPayment,
            'currency' => $data['currency'] ?? 'EGP',
            'reference_id' => $data['reference_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'description' => $data['description'] ?? 'BNPL Down Payment',
            'metadata' => array_merge($data['metadata'] ?? [], [
                'customer_id' => $customerId,
                'customer_name' => $customer->name,
                'installments_count' => $installmentsCount,
                'installment_amount' => $installmentAmount,
                'total_with_interest' => $totalWithInterest,
                'financed_amount' => $financedAmount,
                'interest_rate' => $interestRate,
                'frequency' => $frequency,
                'down_payment' => $downPayment,
                'first_payment_date' => $scheduleDates[0]->format('Y-m-d'),
                'last_payment_date' => $scheduleDates[count($scheduleDates) - 1]->format('Y-m-d'),
            ]),
            'user_id' => $data['user_id'] ?? auth()->id(),
        ]);

        // Create BNPL contract in database
        $this->createBNPLContract(
            transaction: $transaction,
            customerId: $customerId,
            totalAmount: $amount,
            downPayment: $downPayment,
            installmentsCount: $installmentsCount,
            installmentAmount: $installmentAmount,
            frequency: $frequency,
            interestRate: $interestRate,
            scheduleDates: $scheduleDates,
            guaranteeNotes: $data['guarantee_notes'] ?? null,
            notes: $data['notes'] ?? null,
        );

        $this->log('info', 'BNPL contract initiated', [
            'transaction_id' => $transaction->id,
            'customer_id' => $customerId,
            'total_amount' => $amount,
            'installments' => $installmentsCount,
        ]);

        return $transaction->fresh();
    }

    /**
     * Process - BNPL is completed at initiation with down payment.
     */
    public function process(PaymentTransaction $transaction, array $data = []): PaymentTransaction
    {
        return $transaction;
    }

    /**
     * Verify a BNPL contract status.
     */
    public function verify(PaymentTransaction $transaction): PaymentTransaction
    {
        return $transaction;
    }

    /**
     * Refund a BNPL contract (early settlement).
     */
    public function refund(PaymentTransaction $transaction, ?float $amount = null, ?string $reason = null): PaymentTransaction
    {
        throw new PaymentException(
            'BNPL contracts cannot be refunded directly. Please process an early settlement.',
            400,
            gatewayName: $this->getDisplayName()
        );
    }

    /**
     * Get external transaction status.
     */
    public function getExternalTransactionStatus(string $externalTransactionId): array
    {
        return [
            'success' => true,
            'status' => 'completed',
            'message' => 'BNPL is managed internally.',
        ];
    }

    /**
     * Create BNPL contract and installment schedule
     */
    protected function createBNPLContract(
        PaymentTransaction $transaction,
        int $customerId,
        float $totalAmount,
        float $downPayment,
        int $installmentsCount,
        float $installmentAmount,
        string $frequency,
        float $interestRate,
        array $scheduleDates,
        ?string $guaranteeNotes = null,
        ?string $notes = null,
    ): void {
        $companyId = $this->company?->id ?? app('tenant.company_id');
        $financedAmount = $totalAmount - $downPayment;
        $totalWithInterest = $financedAmount * (1 + ($interestRate / 100));

        // Create contract
        $contract = DB::table('bnpl_contracts')->insertGetId([
            'company_id' => $companyId,
            'user_id' => $transaction->user_id,
            'customer_id' => $customerId,
            'contract_number' => 'BNPL-' . strtoupper(Str::random(8)) . '-' . time(),
            'total_amount' => $totalAmount,
            'down_payment' => $downPayment,
            'remaining_amount' => $totalWithInterest,
            'installments_count' => $installmentsCount,
            'installment_amount' => $installmentAmount,
            'frequency' => $frequency,
            'interest_rate' => $interestRate,
            'status' => 'active',
            'start_date' => Carbon::today(),
            'end_date' => $scheduleDates[count($scheduleDates) - 1],
            'next_payment_date' => $scheduleDates[0],
            'paid_installments' => 0,
            'guarantee_notes' => $guaranteeNotes,
            'metadata' => json_encode([]),
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create installment schedule
        foreach ($scheduleDates as $index => $dueDate) {
            $installmentNumber = $index + 1;
            DB::table('bnpl_installments')->insert([
                'company_id' => $companyId,
                'bnpl_contract_id' => $contract,
                'payment_transaction_id' => $index === 0 ? $transaction->id : null,
                'installment_number' => $installmentNumber,
                'amount' => $installmentAmount,
                'paid_amount' => $index === 0 ? $downPayment : 0,
                'due_date' => $dueDate,
                'paid_at' => $index === 0 ? now() : null,
                'status' => $index === 0 ? 'paid' : 'pending',
                'late_fee' => 0,
                'remaining' => $installmentAmount - ($index === 0 ? $downPayment : 0),
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Update transaction with contract info
        $transaction->metadata = array_merge($transaction->metadata ?? [], [
            'bnpl_contract_id' => $contract,
        ]);
        $transaction->save();
    }

    /**
     * Generate installment schedule dates.
     */
    protected function generateScheduleDates(int $count, string $frequency): array
    {
        $dates = [];
        $currentDate = Carbon::today();

        for ($i = 0; $i < $count; $i++) {
            $currentDate = match ($frequency) {
                'weekly' => $currentDate->addWeek(),
                'biweekly' => $currentDate->addWeeks(2),
                'monthly' => $currentDate->addMonth(),
                'quarterly' => $currentDate->addMonths(3),
                'yearly' => $currentDate->addYear(),
                default => $currentDate->addMonth(),
            };
            $dates[] = $currentDate->copy();
        }

        return $dates;
    }

    /**
     * Record an installment payment.
     */
    public function recordInstallmentPayment(int $contractId, float $amount, ?string $notes = null): PaymentTransaction
    {
        $contract = DB::table('bnpl_contracts')->find($contractId);
        if (!$contract) {
            throw new PaymentException('BNPL contract not found.', 404);
        }

        // Find the next pending installment
        $installment = DB::table('bnpl_installments')
            ->where('bnpl_contract_id', $contractId)
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->first();

        if (!$installment) {
            throw new PaymentException('No pending installments found.', 400);
        }

        // Create payment transaction for this installment
        $transaction = $this->createTransaction([
            'amount' => $amount,
            'currency' => 'EGP',
            'description' => "BNPL Installment #{$installment->installment_number}",
            'metadata' => [
                'bnpl_contract_id' => $contractId,
                'installment_id' => $installment->id,
                'installment_number' => $installment->installment_number,
            ],
        ]);

        $transaction->markAsCompleted();

        // Update installment
        $lateFee = 0;
        if (Carbon::parse($installment->due_date)->isPast() && $contract->grace_period_days) {
            $daysLate = Carbon::parse($installment->due_date)->diffInDays(now());
            if ($daysLate > ($this->config['grace_period_days'] ?? 3)) {
                $lateFee = $installment->amount * (($this->config['late_fee_percentage'] ?? 2) / 100);
            }
        }

        DB::table('bnpl_installments')
            ->where('id', $installment->id)
            ->update([
                'paid_amount' => $amount,
                'paid_at' => now(),
                'status' => $amount >= $installment->amount ? 'paid' : 'partial',
                'late_fee' => $lateFee,
                'remaining' => max(0, $installment->amount - $amount),
                'payment_transaction_id' => $transaction->id,
                'updated_at' => now(),
            ]);

        // Update contract
        $paidInstallments = DB::table('bnpl_installments')
            ->where('bnpl_contract_id', $contractId)
            ->whereIn('status', ['paid', 'partial'])
            ->count();

        $remainingAmount = DB::table('bnpl_installments')
            ->where('bnpl_contract_id', $contractId)
            ->where('status', 'pending')
            ->sum('amount');

        $nextPayment = DB::table('bnpl_installments')
            ->where('bnpl_contract_id', $contractId)
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->first();

        $contractStatus = $paidInstallments >= $contract->installments_count ? 'completed' : 'active';

        DB::table('bnpl_contracts')
            ->where('id', $contractId)
            ->update([
                'paid_installments' => $paidInstallments,
                'remaining_amount' => $remainingAmount,
                'next_payment_date' => $nextPayment?->due_date,
                'status' => $contractStatus,
                'updated_at' => now(),
            ]);

        $this->log('info', 'BNPL installment paid', [
            'contract_id' => $contractId,
            'installment' => $installment->installment_number,
            'amount' => $amount,
        ]);

        return $transaction->fresh();
    }

    /**
     * Get overdue installments for notification/reminder.
     */
    public function getOverdueInstallments(int $companyId, int $daysOverdue = 3): array
    {
        return DB::table('bnpl_installments')
            ->join('bnpl_contracts', 'bnpl_installments.bnpl_contract_id', '=', 'bnpl_contracts.id')
            ->join('parties', 'bnpl_contracts.customer_id', '=', 'parties.id')
            ->where('bnpl_installments.company_id', $companyId)
            ->where('bnpl_installments.status', 'pending')
            ->where('bnpl_installments.due_date', '<', Carbon::now()->subDays($daysOverdue))
            ->select(
                'bnpl_installments.*',
                'bnpl_contracts.contract_number',
                'bnpl_contracts.customer_id',
                'parties.name as customer_name',
                'parties.phone as customer_phone'
            )
            ->get()
            ->toArray();
    }

    /**
     * Process early settlement of a BNPL contract.
     */
    public function earlySettlement(int $contractId, float $discountPercentage = 0): PaymentTransaction
    {
        $contract = DB::table('bnpl_contracts')->find($contractId);
        if (!$contract) {
            throw new PaymentException('BNPL contract not found.', 404);
        }

        if ($contract->status === 'completed') {
            throw new PaymentException('Contract is already completed.', 400);
        }

        // Calculate settlement amount
        $pendingInstallments = DB::table('bnpl_installments')
            ->where('bnpl_contract_id', $contractId)
            ->where('status', 'pending')
            ->get();

        $remainingTotal = $pendingInstallments->sum('amount');
        $discountAmount = $remainingTotal * ($discountPercentage / 100);
        $settlementAmount = $remainingTotal - $discountAmount;

        // Create settlement transaction
        $transaction = $this->createTransaction([
            'amount' => $settlementAmount,
            'currency' => 'EGP',
            'description' => "BNPL Early Settlement - Contract {$contract->contract_number}",
            'metadata' => [
                'bnpl_contract_id' => $contractId,
                'settlement_type' => 'early',
                'original_remaining' => $remainingTotal,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
            ],
        ]);

        $transaction->markAsCompleted();

        // Mark all pending installments as paid
        foreach ($pendingInstallments as $installment) {
            $proportion = $installment->amount / $remainingTotal;
            $paidAmount = $settlementAmount * $proportion;

            DB::table('bnpl_installments')
                ->where('id', $installment->id)
                ->update([
                    'paid_amount' => $paidAmount,
                    'paid_at' => now(),
                    'status' => 'paid',
                    'remaining' => 0,
                    'payment_transaction_id' => $transaction->id,
                    'updated_at' => now(),
                ]);
        }

        // Update contract as completed
        DB::table('bnpl_contracts')
            ->where('id', $contractId)
            ->update([
                'paid_installments' => $contract->installments_count,
                'remaining_amount' => 0,
                'status' => 'completed',
                'updated_at' => now(),
            ]);

        $this->log('info', 'BNPL early settlement completed', [
            'contract_id' => $contractId,
            'settlement_amount' => $settlementAmount,
            'discount' => $discountAmount,
        ]);

        return $transaction->fresh();
    }
}