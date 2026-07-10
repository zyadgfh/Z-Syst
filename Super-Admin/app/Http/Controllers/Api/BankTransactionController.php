<?php

namespace App\Http\Controllers\Api;

use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BankTransactionController extends Controller
{
    use HasUploader;

    public function index()
    {
        $business_id = auth()->user()->business_id;
        $payment_type_id = request('bank_id');

        $data = Transaction::with('user:id,name', 'branch:id,name')
            ->where('business_id', $business_id)
            ->where(function ($query) use ($payment_type_id) {
                $query->where('payment_type_id', $payment_type_id)
                    ->orWhere('from_bank', $payment_type_id)
                    ->orWhere('to_bank', $payment_type_id);
            })
            ->latest()
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
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


    public function store(Request $request)
    {
        $request->validate([
            'from' => 'required|exists:payment_types,id',
            'type' => 'nullable|in:credit,debit',
            'transaction_type' => 'required|in:bank_to_bank,bank_to_cash,adjust_bank',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,svg',
            'note' => 'nullable|string',
        ]);

        $business_id = auth()->user()->business_id;
        $amount = $request->amount ?? 0;
        $type = 'transfer';

        DB::beginTransaction();

        try {
            $fromBank = PaymentType::find($request->from);

            // Prevent transferring to the same bank
            if ($request->transaction_type == 'bank_to_bank' && $request->from == $request->to) {
                return response()->json([
                    'message' => 'Cannot transfer between the same bank account.'
                ], 400);
            }

            // Update balances based on transaction type
            if ($request->transaction_type == 'bank_to_bank') {
                $toBank = PaymentType::find($request->to);

                if ($fromBank->balance < $amount) {
                    return response()->json([
                        'message' => 'Insufficient balance in source bank account.'
                    ], 400);
                }

                $fromBank->decrement('balance', $amount);
                $toBank->increment('balance', $amount);

            } elseif ($request->transaction_type == 'bank_to_cash') {
                if ($fromBank->balance < $amount) {
                    return response()->json([
                        'message' => 'Insufficient balance in selected bank.'
                    ], 400);
                }

                $fromBank->decrement('balance', $amount);

            } elseif ($request->transaction_type == 'adjust_bank') {
                $type = $request->type;
                if ($type == 'credit' && $fromBank->balance < $amount) {
                    return response()->json([
                        'message' => 'Cannot decrease below zero balance.'
                    ], 400);
                }
                if( $type == 'credit'){
                    $fromBank->increment('balance', $amount);
                }else{
                    $fromBank->decrement('balance', $amount);
                }
            }

            $data = Transaction::create([
                'business_id' => $business_id,
                'user_id' => auth()->id(),
                'type' => $type,
                'platform' => 'bank',
                'transaction_type' => $request->transaction_type,
                'amount' => $amount,
                'from_bank' => $request->from,
                'to_bank' => ($request->transaction_type == 'bank_to_bank') ? $request->to : null,
                'date' => $request->date ?? now(),
                'image' => $request->image ? $this->upload($request, 'image') : NULL,
                'note' => $request->note,
            ]);

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $data
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 406);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'from' => 'required|exists:payment_types,id',
            'type' => 'nullable|in:credit,debit',
            'transaction_type' => 'required|in:bank_to_bank,bank_to_cash,adjust_bank',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,svg',
            'note' => 'nullable|string',
        ]);

        $transaction = Transaction::findOrFail($id);
        $fromBank = PaymentType::find($request->from);
        $newAmount = $request->amount;
        $newType = $request->transaction_type;

        DB::beginTransaction();

        try {
            // Transaction type Same
            if ($transaction->transaction_type === $request->transaction_type) {

                if ($newType === 'bank_to_bank') {
                    $toBank = PaymentType::find($request->to);

                    // Adjust balance difference
                    $diff = $newAmount - $transaction->amount;
                    if ($fromBank->balance < $diff && $diff > 0) {
                        return response()->json(['message' => 'Insufficient balance.'], 400);
                    }

                    $fromBank->decrement('balance', $diff);
                    $toBank->increment('balance', $diff);

                } elseif ($newType === 'bank_to_cash') {
                    $diff = $newAmount - $transaction->amount;
                    if ($fromBank->balance < $diff && $diff > 0) {
                        return response()->json(['message' => 'Insufficient balance.'], 400);
                    }
                    $fromBank->decrement('balance', $diff);

                } elseif ($newType === 'adjust_bank') {
                    $bankType = $request->type;
                    $oldType = $transaction->type;
                    $oldAmount = $transaction->amount;

                    if ($bankType === $oldType) {
                        // Same type: adjust by difference
                        $diff = $newAmount - $oldAmount;
                        if ($bankType == 'credit') {
                            $fromBank->increment('balance', $diff);
                        } else {
                            $fromBank->decrement('balance', $diff);
                        }
                    } else {
                        // Different type: reverse old and apply new
                        if ($oldType == 'credit') $fromBank->decrement('balance', $oldAmount);
                        else $fromBank->increment('balance', $oldAmount);

                        if ($bankType == 'credit') $fromBank->increment('balance', $newAmount);
                        else $fromBank->decrement('balance', $newAmount);
                    }
                }

            }
            // Transaction type changed
            else {
                // Reverse old transaction completely
                if ($transaction->transaction_type === 'bank_to_bank') {
                    $oldFrom = PaymentType::find($transaction->from_bank);
                    $oldTo = PaymentType::find($transaction->to_bank);
                    $oldFrom->increment('balance', $transaction->amount);
                    $oldTo->decrement('balance', $transaction->amount);
                } elseif ($transaction->transaction_type === 'bank_to_cash') {
                    $oldFrom = PaymentType::find($transaction->from_bank);
                    $oldFrom->increment('balance', $transaction->amount);
                } elseif ($transaction->transaction_type === 'adjust_bank') {
                    $oldFrom = PaymentType::find($transaction->from_bank);
                    $transaction->type === 'credit'
                        ? $oldFrom->decrement('balance', $transaction->amount)
                        : $oldFrom->increment('balance', $transaction->amount);
                }

                // Apply new transaction
                if ($newType === 'bank_to_bank') {
                    $toBank = PaymentType::find($request->to);
                    if ($fromBank->balance < $newAmount) {
                        return response()->json(['message' => 'Insufficient balance.'], 400);
                    }
                    $fromBank->decrement('balance', $newAmount);
                    $toBank->increment('balance', $newAmount);
                } elseif ($newType === 'bank_to_cash') {
                    if ($fromBank->balance < $newAmount) {
                        return response()->json(['message' => 'Insufficient balance.'], 400);
                    }
                    $fromBank->decrement('balance', $newAmount);
                } elseif ($newType === 'adjust_bank') {
                    if ($request->type == 'credit') $fromBank->increment('balance', $newAmount);
                    else $fromBank->decrement('balance', $newAmount);
                }
            }

            // Update transaction record
            $transaction->update([
                'type' => $newType === 'adjust_bank' ? $request->type : 'transfer',
                'transaction_type' => $newType,
                'amount' => $newAmount,
                'from_bank' => $request->from,
                'to_bank' => $newType === 'bank_to_bank' ? $request->to : null,
                'date' => $request->date ?? now(),
                'image' => $request->image ? $this->upload($request, 'image', $transaction->image) : $transaction->image,
                'note' => $request->note,
            ]);

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $transaction,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $transaction = Transaction::findOrFail($id);

        DB::beginTransaction();

        try {
            $fromBank = PaymentType::find($transaction->from_bank);
            $toBank = PaymentType::find($transaction->to_bank);
            $amount = $transaction->amount;

            // Allow only bank platform transactions to be deleted
            if ($transaction->platform !== 'bank') {
                return response()->json([
                    'message' => 'Cannot delete here, please delete from ' . ucfirst($transaction->platform) . ' section.',
                ], 400);
            }

            // Reverse balance changes based on transaction type
            switch ($transaction->transaction_type) {
                case 'bank_to_bank':
                    if ($toBank && $fromBank) {
                        // Ensure receiver bank has enough balance before reversing
                        if ($toBank->balance < $amount) {
                            return response()->json([
                                'message' => 'Insufficient balance in ' . $toBank->name . ' to reverse this transaction.',
                            ], 400);
                        }

                        $fromBank->increment('balance', $amount);
                        $toBank->decrement('balance', $amount);
                    }
                    break;

                case 'bank_to_cash':
                    if ($fromBank) {
                        $fromBank->increment('balance', $amount);
                    }
                    break;

                case 'adjust_bank':
                    if ($fromBank) {
                        if ($transaction->type === 'credit') {
                            // Previously increased, so now decrease
                            if ($fromBank->balance < $amount) {
                                return response()->json([
                                    'message' => 'Insufficient balance in ' . $fromBank->name . ' to reverse this transaction.',
                                ], 400);
                            }
                            $fromBank->decrement('balance', $amount);
                        } else {
                            // Previously decreased, so now increase
                            $fromBank->increment('balance', $amount);
                        }
                    }
                    break;
            }

            if (file_exists($transaction->image)) {
                Storage::delete($transaction->image);
            }

            $transaction->delete();

            DB::commit();

            return response()->json([
                'message' => __('Data deleted successfully.'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

}
