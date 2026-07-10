<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BkashTokenizePaymentController extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json(['message' => 'Bkash placeholder']);
    }
}
