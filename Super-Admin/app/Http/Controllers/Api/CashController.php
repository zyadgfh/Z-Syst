<?php

namespace App\Http\Controllers\Api;

use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class CashController extends Controller
{
    use HasUploader;

    public function index(Request $request)
    {
        $business_id = auth()->user()->business_id;

        $data = Transaction::with('user:id,name')
            ->where('business_id', $business_id)
            ->whereIn('transaction_type', ['cash_payment', 'bank_to_cash', 'cash_to_bank', 'adjust_cash', 'cheque_to_cash'])
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

        $total_balance = cash_balance();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
            'total_balance' => $total_balance,
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
            'to' => 'nullable|exists:payment_types,id',
            'type' => 'nullable|in:credit,debit',
            'transaction_type' => 'required|in:cash_to_bank,adjust_cash',
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
            // Cash to Bank
            if ($request->transaction_type === 'cash_to_bank') {
                $toBank = PaymentType::findOrFail($request->to);
                // increase target bank balance
                $toBank->increment('balance', $amount);

                // Adjust Cash
            } elseif ($request->transaction_type === 'adjust_cash') {
                $type = $request->type;
            }

            // Store transaction record
            $data = Transaction::create([
                'business_id' => $business_id,
                'user_id' => auth()->id(),
                'type' => $type,
                'platform' => 'cash',
                'transaction_type' => $request->transaction_type,
                'amount' => $amount,
                'from_bank' => null,
                'to_bank' => ($request->transaction_type === 'cash_to_bank') ? $request->to : null,
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
            DB::rollBack();
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'to' => 'nullable|exists:payment_types,id',
            'type' => 'nullable|in:credit,debit',
            'transaction_type' => 'required|in:cash_to_bank,adjust_cash',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,svg',
            'note' => 'nullable|string',
        ]);

        $transaction = Transaction::findOrFail($id);
        $newAmount = $request->amount ?? 0;
        $newTransactionType = $request->transaction_type;
        $type = 'transfer';

        DB::beginTransaction();

        try {
            // Transaction type is the same
            if ($transaction->transaction_type === $newTransactionType) {
                if ($newTransactionType === 'cash_to_bank') {
                    $toBank = PaymentType::findOrFail($request->to);

                    // Adjust balance difference
                    $diff = $newAmount - $transaction->amount;

                    // Prevent negative balance
                    if ($toBank->balance + $diff < 0) {
                        return response()->json([
                            'message' => 'Cannot update: updated bank balance would be negative.'
                        ], 400);
                    }

                    $toBank->increment('balance', $diff);

                } elseif ($newTransactionType === 'adjust_cash') {
                    $type = $request->type;
                }
            }
            // Transaction type changed
            else {
                // Reverse old transaction effect
                if ($transaction->transaction_type === 'cash_to_bank' && $transaction->to_bank) {
                    $prevBank = PaymentType::find($transaction->to_bank);
                    if ($prevBank) {
                        if ($prevBank->balance < $transaction->amount) {
                            return response()->json([
                                'message' => 'Cannot update: insufficient balance in ' . $prevBank->name . ' to reverse previous transaction.'
                            ], 400);
                        }
                        $prevBank->decrement('balance', $transaction->amount);
                    }
                }

                // Apply new transaction effect
                if ($newTransactionType === 'cash_to_bank') {
                    $toBank = PaymentType::findOrFail($request->to);
                    $toBank->increment('balance', $newAmount);
                    $type = 'transfer';
                } elseif ($newTransactionType === 'adjust_cash') {
                    $type = $request->type;
                }
            }

            // Update transaction record
            $transaction->update([
                'type' => $type,
                'transaction_type' => $newTransactionType,
                'amount' => $newAmount,
                'to_bank' => ($newTransactionType === 'cash_to_bank') ? $request->to : null,
                'date' => $request->date ?? now(),
                'image' => $request->image ? $this->upload($request, 'image') : $transaction->image,
                'note' => $request->note,
            ]);

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $transaction,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }

    }

    public function destroy(string $id)
    {

        $transaction = Transaction::findOrFail($id);

        DB::beginTransaction();

        try {
            // Allow only "cash" platform transactions to be deleted
            if ($transaction->platform !== 'cash') {
                return response()->json([
                    'message' => 'Cannot delete here, please delete from ' . ucfirst($transaction->platform) . ' section.',
                ], 400);
            }

            $amount = $transaction->amount;
            $toBank = $transaction->to_bank ? PaymentType::find($transaction->to_bank) : null;

            // Reverse balance changes based on transaction type
            switch ($transaction->transaction_type) {
                case 'cash_to_bank':
                    if ($toBank) {
                        // Ensure bank has enough balance to reverse
                        if ($toBank->balance < $amount) {
                            return response()->json([
                                'message' => 'Cannot delete: bank balance would go negative.',
                            ], 400);
                        }
                        $toBank->decrement('balance', $amount);
                    }
                    break;

                case 'adjust_cash':
                    // Cash is static, so no bank adjustments needed
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
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

}
