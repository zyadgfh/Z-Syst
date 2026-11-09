<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetails;
use App\Models\Stock;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = SaleReturn::with('sale:id,party_id,is_paid,total_amount,due_amount,paid_amount,invoice_number', 'sale.party:id,name', 'details')
            ->whereBetween('return_date', [$request->input('start_date'), $request->input('end_date')])
            ->where('business_id', auth()->user()->business_id)
            ->latest()
            ->paginate($request->input('per_page', 10));

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
            'return_date' => 'required',
            'sale_detail_id' => 'required|array',
            'return_amount' => 'required|array',
            'return_qty' => 'required|array',
        ]);

        $sale_return = TransactionHelper::run(function () use ($request) {
            $business_id = auth()->user()->business_id;

            $sale_return = SaleReturn::create($request->only([
        'sale_id',
        'invoice_no',
        'return_date'
    ]) + [
                'business_id' => $business_id,
            ]);

            $sale = Sale::findOrFail($request->sale_id);
            $prev_sale_data = $sale->sale_data ?? $sale->load('details.product:id,product_name');

            $party = Party::find($sale->party_id);
            $total_return_amount = array_sum($request->return_amount);

            if ($party) {
                $party->update([
                    'due' => $party->due > $total_return_amount ? $party->due - $total_return_amount : 0,
                ]);
            }

            // Update Sale record
            $sale->update([
                'sale_data' => $prev_sale_data,
                'due_amount' => $request->due_amount,
                'paid_amount' => $request->paid_amount,
                'total_amount' => $request->total_amount,
                'discount_amount' => $request->discount_amount,
                'loss_profit' => array_sum($request->loss_profit ?? []) - ($request->discount_amount ?? 0),
            ]);

            $data = [];
            foreach ($request->sale_detail_id as $key => $detail_id) {
                $sale_detail = SaleDetails::findOrFail($detail_id);

                // Update stock for the specific batch
                $batch = Stock::where('product_id', $sale_detail->product_id)
                    ->when($sale_detail->batch_no ?? false, function ($query) use ($sale_detail) {
                        return $query->where('batch_no', $sale_detail->batch_no);
                    })
                    ->first();

                if (! $batch) {
                    throw new BusinessRuleException(
                        ErrorCode::NOT_FOUND_BATCH,
                        __('errors.batch_not_found'),
                        [
                            'product_id' => $sale_detail->product_id,
                            'batch_no' => $sale_detail->batch_no,
                        ]
                    );
                }

                $batch->increment('product_stock', $request->return_qty[$key]);

                // Update SaleDetail record
                $sale_detail->update([
                    'loss_profit' => $request->loss_profit[$key] ?? $sale_detail->loss_profit,
                    'quantities' => $sale_detail->quantities - $request->return_qty[$key],
                ]);

                $data[] = [
                    'business_id' => $business_id,
                    'sale_detail_id' => $detail_id,
                    'sale_return_id' => $sale_return->id,
                    'return_qty' => $request->return_qty[$key],
                    'return_amount' => $request->return_amount[$key],
                ];
            }

            SaleReturnDetails::insert($data);

            return $sale_return;
        }, 'sale-return:store', ['sale_id' => $request->sale_id]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $sale_return,
        ]);
    }

    public function show($id)
    {
        $data = SaleReturn::with(
            'sale:id,party_id,is_paid,total_amount,due_amount,paid_amount,invoice_number',
            'sale.party:id,name',
            'details'
        )->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }
}
