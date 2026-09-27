<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanSubscribe;

class ZSystSubscribesController extends Controller
{
    public function index()
    {
        $subscribes = PlanSubscribe::where('user_id', auth()->id())->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $subscribes,
        ]);
    }
}
