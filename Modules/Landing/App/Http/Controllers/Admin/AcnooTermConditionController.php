<?php

namespace Modules\Landing\App\Http\Controllers\Admin;

use App\Models\Option;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class AcnooTermConditionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:term-condition-read')->only('index');
        $this->middleware('permission:term-condition-update')->only('update');
    }

    public function index()
    {
        $term_condition = Option::where('key', 'term-condition')->first();
        return view('landing::admin.settings.term-condition.index', compact('term_condition'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
        ]);

        Option::updateOrCreate(
            ['key' => 'term-condition'],
            ['value' => ['description' => $request->description]]
        );

        Cache::forget('term-condition');
        return response()->json(__('Term And Condition updated successfully.'));
    }
}
