<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    use HasUploader;

    public function __construct()
    {
        $this->middleware('permission:settings-read')->only('index');
        $this->middleware('permission:settings-update')->only('update');
    }

    public function index()
    {
        $general = Option::where('key', 'general')->first();

        return view('admin.settings.general', compact('general'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'logo' => 'nullable|image',
            'favicon' => 'nullable|image',
            'common_header_logo' => 'nullable|image',
            'footer_logo' => 'nullable|image',
            'admin_logo' => 'nullable|image',
            'frontend_logo' => 'nullable|image',
        ]);

        $general = Option::findOrFail($id);
        Cache::forget($general->key);
        $general->update([
            'value' => $request->except('_token', '_method', 'logo', 'favicon', 'common_header_logo', 'footer_logo', 'admin_logo', 'frontend_logo') + [
                'favicon' => $request->favicon ? $this->upload($request, 'favicon', $general->favicon) : $general->value['favicon'],
                'admin_logo' => $request->admin_logo ? $this->upload($request, 'admin_logo', $general->admin_logo) : $general->value['admin_logo'],
                'frontend_logo' => $request->frontend_logo ? $this->upload($request, 'frontend_logo', $general->frontend_logo) : $general->value['frontend_logo'],
            ],
        ]);

        return response()->json([
            'message' => __('General Setting updated successfully'),
            'redirect' => route('admin.settings.index'),
        ]);
    }

    public function toggleDarkMode(Request $request)
    {
        $darkMode = $request->input('dark_mode', false);
        session(['dark_mode' => $darkMode]);

        // Set cookie for persistence
        cookie()->queue('dark_mode', $darkMode ? 'true' : 'false', 525600); // 1 year

        return response()->json([
            'dark_mode' => $darkMode,
        ]);
    }
}
