<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AssistantEngine;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:assistant-use');
    }

    public function index()
    {
        return view('admin.assistant.index');
    }

    public function ask(Request $request)
    {
        $data = $request->validate(['q' => 'required|string|max:300']);

        return response()->json(['success' => true] + app(AssistantEngine::class)->answer(
            (int) $request->user()->business_id, $data['q']
        ));
    }
}
