<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductSetting;
use Illuminate\Http\Request;

class ProductSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = ProductSetting::where('business_id', auth()->user()->business_id)->first();

        if ($data) {
            $responseData = $data;
        } else {
            $responseData = [
                'business_id' => auth()->user()->business_id,
                'modules' => [
                    // Default fields = "1"
                    'show_product_type_single' => "1",
                    'show_product_category' => "1",
                    'show_alert_qty' => "1",
                    'show_product_unit' => "1",
                    'show_exclusive_price' => "1",
                    'show_inclusive_price' => "1",
                    'show_profit_percent' => "1",
                    'show_product_sale_price' => "1",
                    'show_product_price' => "1",
                    'show_product_stock' => "1",

                    'default_sale_price' => null,
                    'default_wholesale_price' => null,
                    'default_dealer_price' => null,
                    'default_batch_no' => null,
                    'expire_date_type' => null,
                    'mfg_date_type' => null,
                    'default_expired_date' => null,
                    'default_mfg_date' => null,

                    'show_product_code' => "0",
                    'show_product_brand' => "0",
                    'show_model_no' => "0",
                    'show_product_manufacturer' => "0",
                    'show_product_image' => "0",
                    'show_vat_id' => "0",
                    'show_vat_type' => "0",
                    'show_action' => "0",
                    'show_product_dealer_price' => "0",
                    'show_product_wholesale_price' => "0",
                    'show_batch_no' => "0",
                    'show_expire_date' => "0",
                    'show_mfg_date' => "0",
                    'show_product_type_variant' => "0",
                    'show_product_batch_no' => "0",
                    'show_product_expire_date' => "0",
                    'show_product_type_combo' => "0",
                    'show_warehouse' => "0",
                    'show_rack' => "0",
                    'show_shelf' => "0",
                    'show_guarantee' => "0",
                    'show_warranty' => "0",
                    'show_serial' => "0",
                ],
            ];
        }

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $responseData,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'show_product_name' => 'nullable|integer',
            'show_product_price' => 'nullable|integer',
            'show_product_code' => 'nullable|integer',
            'show_product_stock' => 'nullable|integer',
            'show_product_sale_price' => 'nullable|integer',
            'show_product_dealer_price' => 'nullable|integer',
            'show_product_wholesale_price' => 'nullable|integer',
            'show_product_unit' => 'nullable|integer',
            'show_product_brand' => 'nullable|integer',
            'show_product_category' => 'nullable|integer',
            'show_product_manufacturer' => 'nullable|integer',
            'show_product_image' => 'nullable|integer',
            'show_expire_date' => 'nullable|integer',
            'show_alert_qty' => 'nullable|integer',
            'show_vat_id' => 'nullable|integer',
            'show_vat_type' => 'nullable|integer',
            'show_exclusive_price' => 'nullable|integer',
            'show_inclusive_price' => 'nullable|integer',
            'show_profit_percent' => 'nullable|integer',
            'show_capacity' => 'nullable|integer',
            'show_weight' => 'nullable|integer',
            'show_color' => 'nullable|integer',
            'show_type' => 'nullable|integer',
            'show_size' => 'nullable|integer',
            'show_batch_no' => 'nullable|integer',
            'show_mfg_date' => 'nullable|integer',
            'show_model_no' => 'nullable|integer',
            'default_sale_price' => 'nullable|integer',
            'default_wholesale_price' => 'nullable|integer',
            'default_dealer_price' => 'nullable|integer',
            'show_product_type_single' => 'nullable|integer',
            'show_product_type_variant' => 'nullable|integer',
            'show_product_type_combo' => 'nullable|integer',
            'show_warehouse' => 'nullable|integer',
            'show_action' => 'nullable|integer',
            'show_rack' => 'nullable|integer',
            'show_shelf' => 'nullable|integer',
            'show_guarantee' => 'nullable|integer',
            'show_warranty' => 'nullable|integer',
            'show_serial' => 'nullable|integer',
            'default_batch_no' => 'nullable|integer',
            'default_expired_date' => 'nullable|integer',
            'default_mfg_date' => 'nullable|integer',
            'expire_date_type' => 'nullable|integer',
            'mfg_date_type' => 'nullable|integer',
            'show_product_batch_no' => 'nullable|integer',
            'show_product_expire_date' => 'nullable|integer'
        ]);

        ProductSetting::updateOrCreate(
            ['business_id' => auth()->user()->business_id],
            ['modules' => $request->all()]
        );

        return response()->json([
            'message' => __('Data saved successfully.'),
        ]);
    }
}
