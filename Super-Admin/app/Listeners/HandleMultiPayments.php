<?php

namespace App\Listeners;

use App\Events\MultiPaymentProcessed;
use App\Models\Party;
use App\Models\PaymentType;
use App\Models\Transaction;

class HandleMultiPayments
{
    /**
     * Handle the event.
     */
    public function handle(MultiPaymentProcessed $event): void
    {
        $payments = $event->payments;
        $reference_id = $event->reference_id;
        $platform = $event->platform; // sale, purchase, income, expense, due_collect, due_pay, sale_return, purchase_return, payroll
        $receiveAmount = $event->receiveAmount;
        $party_id = $event->party_id;
        $business_id = auth()->user()->business_id;
        $branchId = auth()->user()->branch_id ?? auth()->user()->active_branch_id;

        // If main payment is present as "main", convert it to normal format
        if (isset($payments['main'])) {
            $mainPayment = $payments['main'];
            $mainPayment['amount'] = $receiveAmount;
            $payments = [$mainPayment] + $payments;
            unset($payments['main']);
        }

        $transactionsData = [];
        $txnCount = (int) Transaction::where('business_id', $business_id)->count() + 1;

        foreach ($payments as $index => $payment) {
            $type = $payment['type'] ?? null;
            $amount = $payment['amount'] ?? 0;
            $cheque_number = $payment['cheque_number'] ?? null;

            // Validate payment type & amount
            if (!$type || $amount < 0) {
                throw new \Exception(__('Invalid payment type or Amount.'));
            }

            if ($amount == 0) {
                continue;
            }

            $creditPlatforms = ['sale', 'income', 'due_collect', 'purchase_return'];
           // debit platform = purchase, expense, due_pay, sale_return, payroll

            $transactionTypeValue = in_array($platform, $creditPlatforms) ? 'credit' : 'debit';

            // Common transaction base
            $transactionRow = [
                'business_id' => $business_id,
                'user_id' => auth()->id(),
                'reference_id' => $reference_id,
                'branch_id' => $branchId,
                'invoice_no' => "T" . str_pad((int)$txnCount + (int)$index, 2, '0', STR_PAD_LEFT),
                'platform' => $platform,
                'type' => $transactionTypeValue,
                'amount' => $amount,
                'date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'transaction_type' => null,
                'payment_type_id' => null,
                'meta' => null,
            ];

            $lowerType = strtolower($type);

            if ($lowerType === 'cash') {
                $transactionRow['transaction_type'] = 'cash_payment';

            } elseif ($lowerType === 'cheque') {
                $transactionRow['transaction_type'] = 'cheque_payment';
                $transactionRow['type'] = 'pending';
                if (!empty($cheque_number)) {
                    $transactionRow['meta'] = json_encode([
                        'cheque_number' => $cheque_number
                    ]);
                }
            } elseif ($lowerType === 'wallet') {
                $transactionRow['transaction_type'] = 'wallet_payment';
                $party = Party::find($party_id);

                if (!$party) {
                    throw new \Exception(__('When using Wallet as the payment method, you are required to choose a valid customer or supplier.'));
                }

                $walletBalance = $party->wallet ?? 0;

                if ($walletBalance < $amount) {
                    throw new \Exception(__('You have not enough balance in wallet.'));
                }

                $party->decrement('wallet', $amount);
            }
            else {
                // Bank payment
                $paymentType = PaymentType::find($type);
                if (!$paymentType) throw new \Exception(__('Invalid bank payment type.'));

                // Sale & Income -> increment bank
                if (in_array($platform, ['sale', 'income', 'due_collect', 'purchase_return'])) {
                    $paymentType->increment('balance', $amount);
                }
                // Purchase & Expense -> decrement bank
                else {
                    $paymentType->decrement('balance', $amount);
                }

                $transactionRow['transaction_type'] = 'bank_payment';
                $transactionRow['payment_type_id'] = $paymentType->id;
            }

            $transactionsData[] = $transactionRow;
        }

        // Insert all transactions
        if (!empty($transactionsData)) {
            Transaction::insert($transactionsData);
        }
    }
}
