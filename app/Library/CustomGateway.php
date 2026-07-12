<?php

namespace App\Library;

use Illuminate\Http\Request;

class CustomGateway
{
    public function status(Request $request)
    {
        return response()->json([
            'message' => 'Custom gateway configured.',
            'status' => 'ok',
        ]);
    }
}
