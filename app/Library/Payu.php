<?php

namespace App\Library;

use Illuminate\Http\Request;

class Payu
{
    public function view(Request $request)
    {
        return response()->json(['message' => 'PayU view endpoint ready.']);
    }

    public function status(Request $request)
    {
        return response()->json(['message' => 'PayU status endpoint ready.']);
    }
}
