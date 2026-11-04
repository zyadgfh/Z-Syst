<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;

class ZSystPrivacyController extends Controller
{
    public function index()
    {
        $policy = Option::where('key', 'policy')->first()->value;

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $policy ?? 'Privacy Policy',
        ]);
    }
}
