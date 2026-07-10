<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Traits\DateFilterTrait;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use DateFilterTrait;

    public function index(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $transactionsQuery = Transaction::with([
            'paymentType',
            'sale.party',
            'purchase.party',
            'dueCollect.party'
        ])
            ->where('business_id', $businessId);

        if (request('duration')) {
            $this->applyDateFilter($transactionsQuery, request('duration'), 'date', request('from_date'), request('to_date'));
        }

        // Platform filter
        if (request('platform')) {
            $transactionsQuery->where('platform', $request->platform);
        }

        // Party filter
        if (request('party_id')) {
            $transactionsQuery->where(function ($q) use ($request) {

                $q->where(function ($saleQ) use ($request) {
                    $saleQ->where('platform', 'sale')
                        ->whereHas('sale', fn($s) => $s->where('party_id', $request->party_id)
                        );
                });

                $q->orWhere(function ($purQ) use ($request) {
                    $purQ->where('platform', 'purchase')
                        ->whereHas('purchase', fn($p) => $p->where('party_id', $request->party_id)
                        );
                });

                $q->orWhere(function ($dueQ) use ($request) {
                    $dueQ->where('platform', 'due_collect')
                        ->whereHas('dueCollect', fn($d) => $d->where('party_id', $request->party_id)
                        );
                });
            });
        }

        // Summary
        $total_tr_amount = (clone $transactionsQuery)->sum('amount');

        $total_tr_money_in = (clone $transactionsQuery)
            ->where('type', 'credit')
            ->sum('amount');

        $total_tr_money_out = (clone $transactionsQuery)
            ->where('type', 'debit')
            ->sum('amount');

        // No pagination
        $transactions = $transactionsQuery->latest()->get();


        $transactions->transform(function ($transaction) {
            $transaction->total_amount = match($transaction->platform) {
                'sale' => $transaction->sale?->totalAmount ?? 0,
                'purchase' => $transaction->purchase?->totalAmount ?? 0,
                'due_collect' => $transaction->dueCollect?->totalDue ?? 0,
                'sale_return' => $transaction->saleReturn?->sale?->totalAmount ?? 0,
                'purchase_return' => $transaction->purchaseReturn?->purchase?->totalAmount ?? 0,
                default => 0
            };

            return $transaction;
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_amount' => $total_tr_amount,
            'money_in' => $total_tr_money_in,
            'money_out' => $total_tr_money_out,
            'data' => $transactions,
        ]);
    }

    public function moneyReceipt($id)
    {
        $transaction = Transaction::with([
            'paymentType',
            'sale.party',
            'purchase.party',
            'dueCollect.party'
        ])->find($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $transaction,
        ]);
    }
}
