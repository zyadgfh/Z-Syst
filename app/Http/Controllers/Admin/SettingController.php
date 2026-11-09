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
        $allowed = $request->only(['title','description','address','phone','email','copyright','currency','language']);
        $general->update([
            'value' => array_merge((array) $general->value, $allowed, [
                'favicon' => $request->hasFile('favicon') ? $this->upload($request, 'favicon', $general->value['favicon'] ?? null) : ($general->value['favicon'] ?? null),
                'admin_logo' => $request->hasFile('admin_logo') ? $this->upload($request, 'admin_logo', $general->value['admin_logo'] ?? null) : ($general->value['admin_logo'] ?? null),
                'frontend_logo' => $request->hasFile('frontend_logo') ? $this->upload($request, 'frontend_logo', $general->value['frontend_logo'] ?? null) : ($general->value['frontend_logo'] ?? null),
            ]),
        ]);

        return response()->json([
            'message' => __('General Setting updated successfully'),
            'redirect' => route('admin.settings.index'),
        ]);
    }
}
