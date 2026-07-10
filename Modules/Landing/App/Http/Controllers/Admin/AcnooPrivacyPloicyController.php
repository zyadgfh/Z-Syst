<?php

namespace Modules\Landing\App\Http\Controllers\Admin;

use App\Models\Option;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class AcnooPrivacyPloicyController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:privacy-policy-read')->only('index');
        $this->middleware('permission:privacy-policy-update')->only('update');
    }

    public function index()
    {
        $privacy_policy = Option::where('key', 'privacy-policy')->first();
        return view('landing::admin.settings.privacy-policy.index', compact('privacy_policy'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
        ]);

        Option::updateOrCreate(
            ['key' => 'privacy-policy'],
            ['value' => ['description' => $request->description]]
        );

        Cache::forget('privacy-policy');
        return response()->json(__('Privacy And Policy updated successfully.'));
    }
}
