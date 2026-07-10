<?php

namespace App\Http\Controllers\Api;

use App\Models\Income;
use App\Models\Party;
use App\Models\PaymentType;
use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ChequeController extends Controller
{
    public function index(Request $request)
    {
        $business_id = auth()->user()->business_id;

        $data = Transaction::with(['user:id,name','paymentType:id,name'])
            ->where('business_id', $business_id)
            ->whereIn('transaction_type', ['cheque_payment'])
            ->when($request->duration, function ($query) use ($request) {
                $today = Carbon::today();

                if ($request->duration === 'today') {
                    $query->whereDate('date', $today);
                } elseif ($request->duration === 'yesterday') {
                    $query->whereDate('date', Carbon::yesterday());
                } elseif ($request->duration === 'last_seven_days') {
                    $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
                    $endDate = Carbon::now()->format('Y-m-d');
                    $query->whereDate('date', '>=', $startDate)
                        ->whereDate('date', '<=', $endDate);
                } elseif ($request->duration === 'last_thirty_days') {
                    $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
                    $endDate = Carbon::now()->format('Y-m-d');
                    $query->whereDate('date', '>=', $startDate)
                        ->whereDate('date', '<=', $endDate);
                } elseif ($request->duration === 'current_month') {
                    $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                    $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
                    $query->whereDate('date', '>=', $startDate)
                        ->whereDate('date', '<=', $endDate);
                } elseif ($request->duration === 'last_month') {
                    $startDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                    $endDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                    $query->whereDate('date', '>=', $startDate)
                        ->whereDate('date', '<=', $endDate);
                } elseif ($request->duration === 'current_year') {
                    $startDate = Carbon::now()->startOfYear()->format('Y-m-d');
                    $endDate = Carbon::now()->endOfYear()->format('Y-m-d');
                    $query->whereDate('date', '>=', $startDate)
                        ->whereDate('date', '<=', $endDate);
                } elseif ($request->duration === 'custom_date' && $request->from_date && $request->to_date) {
                    $startDate = Carbon::parse($request->from_date)->format('Y-m-d');
                    $endDate = Carbon::parse($request->to_date)->format('Y-m-d');
                    $query->whereDate('date', '>=', $startDate)
                        ->whereDate('date', '<=', $endDate);
                }
            })
            ->latest()
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'date' => 'nullable|date',
            'note' => 'nullable|string',
            'payment_type' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value !== 'cash' && !PaymentType::where('id', $value)->exists()) {
                        $fail(__('The selected payment type is invalid.'));
                    }
                },
            ],
        ]);

        $business_id = auth()->user()->business_id;

        DB::beginTransaction();
        try {
            $transaction = Transaction::findOrFail($request->transaction_id);
            $amount = $transaction->amount;
            $platform = strtolower($transaction->platform);

            $transaction->update([
                'type' => 'deposit',
            ]);

            $newTransactionData = [
                'business_id' => $business_id,
                'user_id' => auth()->id(),
                'type' => 'credit',
                'platform' => 'cheque',
                'amount' => $amount,
                'date' => $request->date ?? now(),
                'note' => $request->note,
                'meta' => [
                    'source_transaction_id' => $request->transaction_id ?? null,
                ],
            ];

            // Cheque deposited into Cash
            if ($request->payment_type === 'cash') {
                $newTransactionData['transaction_type'] = 'cheque_to_cash';
                $newTransactionData['payment_type_id'] = null;
            } else {
                // Cheque deposited into Bank
                $toBank = PaymentType::findOrFail($request->payment_type);
                $toBank->increment('balance', $amount);

                $newTransactionData['transaction_type'] = 'cheque_to_bank';
                $newTransactionData['payment_type_id'] = $toBank->id;
            }

            Transaction::create($newTransactionData);

            // update related platform
            if ($platform == 'sale') {
                $sale = Sale::find($transaction->reference_id);

                updateBalance($amount, 'increment');

                $newPaid = $sale->paidAmount + $amount;
                $expectedTotal = $sale->paidAmount + $sale->dueAmount;
                $newChange = max($newPaid - $expectedTotal, 0);
                $newDue = max($sale->dueAmount - $amount, 0);

                $sale->update([
                    'paidAmount' => $newPaid,
                    'change_amount' => $sale->change_amount + $newChange,
                    'dueAmount' => $newDue,
                    'isPaid' => $newDue > 0 ? 0 : 1,
                ]);

                // update party due
                if ($sale->party_id) {
                    $party = Party::find($sale->party_id);
                    if ($party) {
                        $party->decrement('due', min($amount, $party->due));
                    }
                }
            } elseif ($transaction->platform == 'income') {
                $income = Income::find($transaction->reference_id);
                $income->increment('amount', $transaction->amount);
            }

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 406);
        }
    }

    public function show($id)
    {
        $business_id = auth()->user()->business_id;

        $transaction = Transaction::with('user:id,name', 'branch:id,name')
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'message' => __('Transaction not found.'),
            ], 404);
        }

        return response()->json([
            'message' => __('Transaction fetched successfully.'),
            'data' => $transaction,
        ]);
    }

    public function reopen($TransactionId)
    {
        DB::beginTransaction();
        try {
            // original cheque payment
            $originalTxn = Transaction::findOrFail($TransactionId);

            // Must be cheque_payment
            if ($originalTxn->transaction_type !== 'cheque_payment') {
                return response()->json(['message' => __('This transaction cannot be reopened.')], 400);
            }

            $amount = $originalTxn->amount;
            $business_id = auth()->user()->business_id;

            $depositTxn = Transaction::where('platform', 'cheque')
                ->where('meta->source_transaction_id', $originalTxn->id)
                ->first();

            if (!$depositTxn) {
                return response()->json(['message' => __('Deposited cheque record not found.')], 400);
            }

            // Reverse bank balance only if cheque_to_bank
            if ($depositTxn->transaction_type === 'cheque_to_bank' && $depositTxn->payment_type_id) {
                $bank = PaymentType::find($depositTxn->payment_type_id);
                if ($bank) {
                    $bank->decrement('balance', $amount);
                }
            }

            $platform = strtolower($originalTxn->platform);

            // Reverse Sale or Income
            if ($platform == 'sale') {
                $sale = Sale::find($originalTxn->reference_id);
                if ($sale) {
                    // Reverse paid, change, and due amounts
                    $newPaid = $sale->paidAmount - $amount;
                    $newDue = $sale->dueAmount + $amount;
                    $newChange = max($sale->change_amount - max($sale->paidAmount - ($sale->paidAmount + $sale->dueAmount - $amount), 0), 0);

                    $sale->update([
                        'paidAmount' => max($newPaid, 0),
                        'change_amount' => max($newChange, 0),
                        'dueAmount' => $newDue,
                        'isPaid' => $newDue > 0 ? 0 : 1,
                    ]);

                    // Reverse party due if exists
                    if ($sale->party_id) {
                        $party = Party::find($sale->party_id);
                        if ($party) {
                            $party->increment('due', $amount);
                        }
                    }
                }
            } elseif ($platform == 'income') {
                $income = Income::find($originalTxn->reference_id);
                if ($income) {
                    $income->decrement('amount', $amount);
                }
            }

            $reverseType = $depositTxn->transaction_type === 'cheque_to_cash' ? 'cash_to_cheque' : 'bank_to_cheque';

            // Create reverse debit transaction
            Transaction::create([
                'business_id' => $business_id,
                'user_id' => auth()->id(),
                'type' => 'debit',
                'platform' => 'cheque',
                'amount' => $amount,
                'date' => now(),
                'note' => 'Cheque reopened',
                'transaction_type' => $reverseType,
                'payment_type_id' => $depositTxn->payment_type_id,
                'meta' => [
                    'reverted_transaction_id' => $depositTxn->id
                ]
            ]);

            $originalTxn->update([
                'type' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $originalTxn
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 406);
        }
    }


}
