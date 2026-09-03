<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLanguageRequest;
use Illuminate\Http\Request;

class ZSystLanguageController extends Controller
{
    public function index()
    {
        $data = json_decode(file_get_contents(base_path('lang/langlist.json')), true);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(StoreLanguageRequest $request)
    {

        auth()->user()->update([
            'lang' => $request->lang,
        ]);

        return response()->json([
            'message' => __('Language updated successfully.'),
        ]);
    }
}
