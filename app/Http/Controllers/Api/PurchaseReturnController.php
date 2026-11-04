<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Models\Stock;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = PurchaseReturn::with(
            'purchase:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber',
            'purchase.party:id,name',
            'details'
        )
            ->whereBetween('return_date', [$request->input('start_date'), $request->input('end_date')])
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
            'return_date' => 'required',
            'purchase_detail_id' => 'required|array',
            'return_amount' => 'required|array',
            'return_qty' => 'required|array',
        ]);

        $purchase_return = TransactionHelper::run(function () use ($request) {
            $business_id = auth()->user()->business_id;

            // Create Purchase Return record
            $purchase_return = PurchaseReturn::create($request->all() + [
                'business_id' => $business_id,
            ]);

            $purchase = Purchase::findOrFail($request->purchase_id);
            $purchase_data = $purchase->purchase_data ?? $purchase->load('details.product:id,productName');

            $party = Party::find($purchase->party_id);
            $total_return_amount = array_sum($request->return_amount);

            if ($party) {
                $party->update([
                    'due' => $party->due > $total_return_amount ? $party->due - $total_return_amount : 0,
                ]);
            }

            // Update Purchase record
            $purchase->update([
                'purchase_data' => $purchase_data,
                'dueAmount' => $request->dueAmount,
                'paidAmount' => $request->paidAmount,
                'totalAmount' => $request->totalAmount,
                'discountAmount' => $request->discountAmount,
            ]);

            $data = [];
            foreach ($request->purchase_detail_id as $key => $detail_id) {
                $purchase_detail = PurchaseDetails::findOrFail($detail_id);

                // Update stock for the specific batch
                $batch = Stock::where('product_id', $purchase_detail->product_id)
                    ->when($purchase_detail->batch_no ?? false, function ($query) use ($purchase_detail) {
                        return $query->where('batch_no', $purchase_detail->batch_no);
                    })
                    ->first();

                if (! $batch) {
                    throw new BusinessRuleException(
                        ErrorCode::NOT_FOUND_BATCH,
                        __('errors.batch_not_found'),
                        ['batch_no' => $purchase_detail->batch_no]
                    );
                }

                $batch->decrement('productStock', $request->return_qty[$key]);

                // Update PurchaseDetail record
                $purchase_detail->update([
                    'quantities' => $purchase_detail->quantities - $request->return_qty[$key],
                ]);

                $data[] = [
                    'business_id' => $business_id,
                    'purchase_detail_id' => $detail_id,
                    'return_qty' => $request->return_qty[$key],
                    'purchase_return_id' => $purchase_return->id,
                    'return_amount' => $request->return_amount[$key],
                ];
            }

            PurchaseReturnDetail::insert($data);

            return $purchase_return;
        }, 'purchase-return:store', [
            'purchase_id' => $request->purchase_id,
            'items_count' => count($request->purchase_detail_id ?? []),
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $purchase_return,
        ]);
    }

    public function show($id)
    {
        $data = PurchaseReturn::with(
            'purchase:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber',
            'purchase.party:id,name',
            'details'
        )
            ->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }
}
