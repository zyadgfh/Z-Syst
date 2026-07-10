<?php

namespace App\Http\Controllers\Api;

use App\Events\MultiPaymentProcessed;
use App\Models\Party;
use App\Models\Stock;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Models\PurchaseReturn;
use App\Models\PurchaseDetails;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\PurchaseReturnDetail;

class PurchaseReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = PurchaseReturn::with('purchase:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber', 'purchase.party:id,name', 'details')
            ->whereBetween('return_date', [request()->start_date, request()->end_date])
            ->where('business_id', auth()->user()->business_id)
            ->latest()
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'return_qty' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $purchase = Purchase::with('details')
                ->where('business_id', auth()->user()->business_id)
                ->findOrFail($request->purchase_id);

            // Calculate total discount factor
            $total_discount = $purchase->discountAmount;
            $total_purchase_amount = $purchase->details->sum(fn($detail) => $detail->productPurchasePrice * $detail->quantities);
            $discount_per_unit_factor = $total_purchase_amount > 0 ? $total_discount / $total_purchase_amount : 0;

            $purchase_return = PurchaseReturn::create([
                'business_id' => auth()->user()->business_id,
                'purchase_id' => $request->purchase_id,
                'invoice_no' => $purchase->invoiceNumber,
                'return_date' => now(),
            ]);

            $purchase_return_detail_data = [];
            $total_return_amount = 0;
            $total_return_discount = 0;

            // Loop through each purchase detail and process the return
            foreach ($purchase->details as $key => $detail) {
                $requested_qty = $request->return_qty[$key];

                if ($requested_qty <= 0) {
                    continue;
                }

                // Check if return quantity exceeds the purchased quantity
                if ($requested_qty > $detail->quantities) {
                    return response()->json([
                        'message' => "You can't return more than the ordered quantity of {$detail->quantities}.",
                    ], 400);
                }

                // Calculate per-unit discount and return amounts
                $unit_discount = $detail->productPurchasePrice * $discount_per_unit_factor;
                $return_discount = $unit_discount * $requested_qty;
                $return_amount = ($detail->productPurchasePrice - $unit_discount) * $requested_qty;

                $total_return_amount += $return_amount;
                $total_return_discount += $return_discount;

                // Update stock & purchase details
                Stock::where('id', $detail->stock_id)->decrement('productStock', $requested_qty);

                $detail->quantities -= $requested_qty;
                $detail->timestamps = false;
                $detail->save();

                // Collect return detail data
                $purchase_return_detail_data[] = [
                    'purchase_detail_id' => $detail->id,
                    'purchase_return_id' => $purchase_return->id,
                    'return_qty' => $requested_qty,
                    'business_id' => auth()->user()->business_id,
                    'return_amount' => $return_amount,
                ];
            }

            // Insert purchase return details
            if (!empty($purchase_return_detail_data)) {
                PurchaseReturnDetail::insert($purchase_return_detail_data);
            }

            if ($total_return_amount <= 0) {
                return response()->json("You cannot return an empty product.", 400);
            }

            // Update party dues (if applicable)
            $party = Party::find($purchase->party_id);

            if ($party) {
                $refund_amount = $total_return_amount;

                // If party has due, reduce it first
                if ($party->due > 0) {
                    if ($party->due >= $refund_amount) {
                        $party->decrement('due', $refund_amount);
                        $refund_amount = 0;
                    } else {
                        $refund_amount -= $party->due;
                        $party->update(['due' => 0]);
                    }
                }

                // Any remaining amount should be deducted from wallet
                if ($refund_amount > 0 && $party->wallet > 0) {
                    $deduct = min($party->wallet, $refund_amount);
                    $party->decrement('wallet', $deduct);
                }
            }

            // Calculate remaining return amount
            $remaining_return_amount = max(0, $total_return_amount - $purchase->dueAmount);
            $new_total_amount = max(0, $purchase->totalAmount - $total_return_amount);

            // Update purchase record
            $purchase->update([
                'change_amount' => 0,
                'dueAmount' => max(0, $purchase->dueAmount - $total_return_amount),
                'paidAmount' => max(0, $purchase->paidAmount - min($purchase->paidAmount, $total_return_amount)),
                'totalAmount' => $new_total_amount,
                'discountAmount' => max(0, $purchase->discountAmount - $total_return_discount),
                'isPaid' => $remaining_return_amount > 0 ? 1 : $purchase->isPaid,
            ]);

            $payments = $request->payments ?? [];
            $payments = collect($payments)->map(function ($payment) use ($total_return_amount) {
                $payment['amount'] = $total_return_amount;
                return $payment;
            })->toArray();

            event(new MultiPaymentProcessed(
                $payments,
                $purchase_return->id,
                'purchase_return',
                $total_return_amount ?? 0,
            ));

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $purchase_return,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Transaction failed: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $data = PurchaseReturn::with('purchase:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber', 'purchase.party:id,name', 'details')->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }
}
