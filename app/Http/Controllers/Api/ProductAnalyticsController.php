<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductAnalyticsService;
use Illuminate\Http\Request;

class ProductAnalyticsController extends Controller
{
    public function __construct(private ProductAnalyticsService $service) {}

    public function index(Request $request)
    {
        $request->validate([
            'period'=>'nullable|in:week,month,year,custom',
            'date'=>'nullable|date',
            'date_from'=>'nullable|date',
            'date_to'=>'nullable|date|after_or_equal:date_from',
            'product_id'=>'nullable|integer|min:1',
            'warehouse_id'=>'nullable|integer|min:1',
        ]);

        return response()->json([
            'message'=>__('Data fetched successfully.'),
            'data'=>$this->service->analyze(auth()->user()->business_id,$request->only([
                'period','date','date_from','date_to','product_id','warehouse_id'
            ])),
        ]);
    }
}
