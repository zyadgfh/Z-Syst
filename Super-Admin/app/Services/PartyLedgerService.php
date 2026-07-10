<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\DueCollect;
use App\Models\Transaction;

class PartyLedgerService
{
    public function list($request, $partyId)
    {
        $businessId = auth()->user()->business_id;

        $party = Party::where('business_id', $businessId)->findOrFail($partyId);
        $isCustomer = in_array($party->type, ['Retailer', 'Dealer', 'Wholesaler']);

        /* ================= DATE FILTER ================= */
        $fromDate = null;
        $toDate   = null;

        if ($request->filled('duration')) {
            switch ($request->duration) {
                case 'today':
                    $fromDate = Carbon::today()->startOfDay();
                    $toDate   = Carbon::today()->endOfDay();
                    break;
                case 'yesterday':
                    $fromDate = Carbon::yesterday()->startOfDay();
                    $toDate   = Carbon::yesterday()->endOfDay();
                    break;
                case 'last_seven_days':
                    $fromDate = Carbon::now()->subDays(7);
                    break;
                case 'last_thirty_days':
                    $fromDate = Carbon::now()->subDays(30);
                    break;
                case 'current_month':
                    $fromDate = Carbon::now()->startOfMonth();
                    break;
                case 'last_month':
                    $fromDate = Carbon::now()->subMonth()->startOfMonth();
                    $toDate   = Carbon::now()->subMonth()->endOfMonth();
                    break;
                case 'current_year':
                    $fromDate = Carbon::now()->startOfYear();
                    break;
                case 'custom_date':
                    $fromDate = Carbon::parse($request->from_date);
                    $toDate   = Carbon::parse($request->to_date);
                    break;
            }
        }

        $events = collect();
        $ledger = collect();

        /* ================= OPENING BALANCE ================= */
        if (!$fromDate || $party->created_at > $fromDate) {
            $opening = 0;

            if ($isCustomer) {
                $opening = $party->opening_balance_type === 'advance'
                    ? -$party->opening_balance
                    : $party->opening_balance;
            } else {
                $opening = $party->opening_balance_type === 'advance'
                    ? $party->opening_balance
                    :  -$party->opening_balance;
            }

            $debit_amount = $opening > 0 ? $opening : 0;
            $credit_amount = $opening < 0 ? abs($opening) : 0;
            $condition = in_array($party->branch_id, [auth()->user()->branch_id, auth()->user()->active_branch_id]) || !$party->branch_id || auth()->user()->accessToMultiBranch();

            $events->push([
                'id'            => null,
                'date'          => $party->created_at,
                'invoice_no'    => null,
                'platform'      => 'Opening',
                'debit_amount'  => $condition ? $debit_amount : 0,
                'credit_amount' => $condition ? $credit_amount : 0,
                'balance'       => 0,
                'created_at'    => $party->created_at,
            ]);
        }

        /* ================= CUSTOMER ================= */
        if ($isCustomer) {

            $sales = Sale::where('business_id', $businessId)
                ->where('party_id', $partyId)
                ->get();

            $saleIds = $sales->pluck('id');

            $salePayments = Transaction::where('business_id', $businessId)
                ->where('platform', 'sale')
                ->whereIn('reference_id', $saleIds)
                ->where('transaction_type', '!=', 'wallet_payment')
                ->selectRaw('reference_id, SUM(amount) as paid')
                ->groupBy('reference_id')
                ->pluck('paid', 'reference_id');

            foreach ($sales as $sale) {
                $events->push([
                    'id'            => $sale->id,
                    'date'          => Carbon::parse($sale->saleDate),
                    'invoice_no'    => $sale->invoiceNumber,
                    'platform'      => 'Sales',
                    'debit_amount'  => $sale->actual_total_amount,
                    'credit_amount' => $salePayments[$sale->id] ?? 0,
                    'balance'       => 0,
                    'created_at'    => $sale->created_at,
                ]);
            }

            $dueCollects = DueCollect::where('business_id', $businessId)
                ->where('party_id', $partyId)
                ->get();

            foreach ($dueCollects as $due) {
                $events->push([
                    'id'            => $partyId,
                    'date'          => Carbon::parse($due->paymentDate),
                    'invoice_no'    => $due->invoiceNumber,
                    'platform'      => 'Payment',
                    'debit_amount'  => 0,
                    'credit_amount' => $due->payDueAmount,
                    'balance'       => 0,
                    'created_at'    => $due->created_at,
                ]);
            }

        /* ================= SUPPLIER ================= */
        } else {

            $purchases = Purchase::where('business_id', $businessId)
                ->where('party_id', $partyId)
                ->get();

            $purchaseIds = $purchases->pluck('id');

            $purchasePayments = Transaction::where('business_id', $businessId)
                ->where('platform', 'purchase')
                ->whereIn('reference_id', $purchaseIds)
                ->where('transaction_type', '!=', 'wallet_payment')
                ->selectRaw('reference_id, SUM(amount) as paid')
                ->groupBy('reference_id')
                ->pluck('paid', 'reference_id');

            foreach ($purchases as $purchase) {
                $events->push([
                    'id'            => $purchase->id,
                    'date'          => Carbon::parse($purchase->purchaseDate),
                    'invoice_no'    => $purchase->invoiceNumber,
                    'platform'      => 'Purchase',
                    'debit_amount'  => $purchasePayments[$purchase->id] ?? 0,
                    'credit_amount' => $purchase->totalAmount,
                    'balance'       => 0,
                    'created_at'    => $purchase->created_at,
                ]);
            }

            $dueCollects = DueCollect::where('business_id', $businessId)
                ->where('party_id', $partyId)
                ->get();

            foreach ($dueCollects as $due) {
                $events->push([
                    'id'            => $partyId,
                    'date'          => Carbon::parse($due->paymentDate),
                    'invoice_no'    => $due->invoiceNumber,
                    'platform'      => 'Payment',
                    'debit_amount'  => $due->payDueAmount,
                    'credit_amount' => 0,
                    'balance'       => 0,
                    'created_at'    => $due->created_at,
                ]);
            }
        }

        /* ================= SORT ================= */
        $events = $events->sortBy(fn ($e) => [
            $e['created_at'] ? $e['created_at']->timestamp : 0
        ])->values();

        /* ================= FILTER + BALANCE ================= */
        $runningBalance = 0;

        foreach ($events as $e) {

            if ($fromDate && $e['date'] && $e['date']->lt($fromDate)) continue;
            if ($toDate && $e['date'] && $e['date']->gt($toDate)) continue;

            $runningBalance += $e['debit_amount'];
            $runningBalance -= $e['credit_amount'];

            $ledger->push([
                'id'            => $e['id'],
                'date'          => $e['date'],
                'invoice_no'    => $e['invoice_no'],
                'platform'      => $e['platform'],
                'debit_amount'  => $e['debit_amount'],
                'credit_amount' => $e['credit_amount'],
                'balance'       => $runningBalance,
            ]);
        }

        return $ledger->values();
    }
}
