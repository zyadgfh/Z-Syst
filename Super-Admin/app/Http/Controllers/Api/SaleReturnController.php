<?php

namespace App\Http\Controllers\Api;

use App\Events\MultiPaymentProcessed;
use App\Models\Sale;
use App\Models\Party;
use App\Models\Stock;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use App\Models\SaleReturnDetails;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SaleReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = SaleReturn::with('sale:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber', 'sale.party:id,name', 'details')
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
            'sale_id' => 'required|exists:sales,id',
            'return_qty' => 'required|array',
        ]);

        $business_id = auth()->user()->business_id;

        DB::beginTransaction();
        try {
            $sale = Sale::with('details:id,sale_id,product_id,price,discount,lossProfit,quantities,productPurchasePrice,stock_id,expire_date', 'details.product:id,product_type', 'details.product.combo_products')
                ->where('business_id', $business_id)
                ->findOrFail($request->sale_id);

            // Calculate total discount factor with itemwise discount
            $total_discount = $sale->discountAmount;
            $total_sale_amount = $sale->details->sum(fn($detail) => $detail->price * $detail->quantities);
            $discount_per_unit_factor = $total_sale_amount > 0 ? $total_discount / $total_sale_amount : 0;
            $rounding_amount_per_unit = $sale->details->sum('quantities') > 0 ? $sale->rounding_amount / $sale->details->sum('quantities') : 0;

            $sale_return = SaleReturn::create([
                'business_id' => $business_id,
                'sale_id' => $request->sale_id,
                'invoice_no' => $sale->invoiceNumber,
                'return_date' => now(),
            ]);

            $sale_return_detail_data = [];
            $total_return_amount = 0;
            $total_return_discount = 0;
            $total_loss_profit_adjustment = 0;

            foreach ($sale->details as $key => $detail) {
                $requested_qty = $request->return_qty[$key];

                if ($requested_qty <= 0) {
                    continue;
                }

                if ($requested_qty > $detail->quantities) {
                    return response()->json([
                        'message' => "You can't return more than ordered quantity of {$detail->quantities}.",
                    ], 400);
                }

                $product = $detail->product;

                // Include SaleDetails discount in return calculation
                $unit_discount = $detail->price * $discount_per_unit_factor;
                $item_cart_discount = $detail->discount ?? 0;
                $total_discount_per_unit = $unit_discount + $item_cart_discount;

                $return_discount = $total_discount_per_unit * $requested_qty;
                $return_amount = ($detail->price - $total_discount_per_unit + $rounding_amount_per_unit) * $requested_qty;

                $total_return_amount += $return_amount;
                $total_return_discount += $return_discount;

                if ($product && $product->product_type === 'combo') {

                    $combo_total_purchase = 0;
                    $combo_total_sale = $detail->price * $requested_qty;

                    foreach ($product->combo_products as $comboItem) {
                        $stock = Stock::find($comboItem->stock_id);

                        if (!$stock) {
                            return response()->json([
                                'message' => __("Stock not found for combo item '{$comboItem->product->productName}'"),
                            ], 400);
                        }

                        // increase stock by combo component quantity * returned qty
                        $restore_qty = $comboItem->quantity * $requested_qty;
                        $stock->increment('productStock', $restore_qty);

                        $combo_total_purchase += $comboItem->purchase_price * $restore_qty;
                    }

                    // loss/profit adjustment for combo
                    $loss_profit_adjustment = ($combo_total_sale - $combo_total_purchase) - $return_discount;
                    $total_loss_profit_adjustment += $loss_profit_adjustment;
                }

                else {
                    $stock = Stock::where('id', $detail->stock_id)->first();
                    if (!$stock) {
                        return response()->json(['error' => 'Stock not found.'], 404);
                    }

                    $stock->increment('productStock', $requested_qty);

                    $loss_profit_adjustment = (($detail->price - $stock->productPurchasePrice) * $requested_qty) - $return_discount;
                    $total_loss_profit_adjustment += $loss_profit_adjustment;
                }

                // Update sale details
                $detail->quantities -= $requested_qty;
                $detail->lossProfit -= $loss_profit_adjustment;
                $detail->timestamps = false;
                $detail->save();

                $sale_return_detail_data[] = [
                    'business_id' => $business_id,
                    'sale_detail_id' => $detail->id,
                    'sale_return_id' => $sale_return->id,
                    'return_qty' => $requested_qty,
                    'return_amount' => $return_amount,
                ];
            }

            if (!empty($sale_return_detail_data)) {
                SaleReturnDetails::insert($sale_return_detail_data);
            }

            if ($total_return_amount <= 0) {
                return response()->json("You cannot return an empty product.", 400);
            }

            $remaining_refund = $total_return_amount;

            // Adjust Due
            $new_due = $sale->dueAmount;
            if ($remaining_refund > 0) {
                if ($remaining_refund >= $sale->dueAmount) {
                    $remaining_refund -= $sale->dueAmount;
                    $new_due = 0;
                } else {
                    $new_due = $sale->dueAmount - $remaining_refund;
                    $remaining_refund = 0;
                }
            }

            // Adjust Paid (refund part)
            $new_paid = $sale->paidAmount;
            if ($remaining_refund > 0) {
                if ($remaining_refund >= $sale->paidAmount) {
                    $remaining_refund -= $sale->paidAmount;
                    $new_paid = 0;
                } else {
                    $new_paid = $sale->paidAmount - $remaining_refund;
                    $remaining_refund = 0;
                }
            }

            // total sale amount
            $new_total_amount = max(0, $sale->totalAmount - $total_return_amount);

            $sale->update([
                'change_amount' => 0,
                'dueAmount' => $new_due,
                'paidAmount' => $new_paid,
                'totalAmount' => $new_total_amount,
                'actual_total_amount' => $new_total_amount,
                'discountAmount' => max(0, $sale->discountAmount - $total_return_discount),
                'lossProfit' => $sale->lossProfit - $total_loss_profit_adjustment,
            ]);

            // Party Refund Logic
            $party = Party::find($sale->party_id);

            if ($party) {

                // use leftover refund after due/paid adjustments
                $refund_amount = $remaining_refund;

                // Reduce party due
                if ($party->due > 0) {
                    if ($party->due >= $refund_amount) {
                        $party->decrement('due', $refund_amount);
                        $refund_amount = 0;
                    } else {
                        $refund_amount -= $party->due;
                        $party->update(['due' => 0]);
                    }
                }

                // Add leftover refund to wallet
                if ($refund_amount > 0) {
                    $party->increment('wallet', $refund_amount);
                }
            }

            $payments = $request->payments ?? [];
            $payments = collect($payments)->map(function ($payment) use ($total_return_amount) {
                $payment['amount'] = $total_return_amount;
                return $payment;
            })->toArray();

            event(new MultiPaymentProcessed(
                $payments,
                $sale_return->id,
                'sale_return',
                $total_return_amount ?? 0,
            ));

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $sale_return,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Transaction failed: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $data = SaleReturn::with('sale:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber', 'sale.party:id,name', 'details')->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }
}
