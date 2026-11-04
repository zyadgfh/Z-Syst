<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;

class ZSystSubscriptionsController extends Controller
{
    public function index()
    {
        $plans = Plan::whereStatus(1)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $plans,
        ]);
    }
}
