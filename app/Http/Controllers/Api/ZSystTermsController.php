<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Option;

class ZSystTermsController extends Controller
{
    public function index()
    {
        $term = Option::where('key', 'term')->first()->value;

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $term ?? 'Terms & Conditions.',
        ]);
    }
}
