<?php

namespace Modules\Landing\App\Http\Controllers\Admin;

use App\Models\Option;
use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;


class AcnooWebSettingController extends Controller
{
    use HasUploader;

    public function __construct()
    {
        $this->middleware('permission:web-settings-read')->only('index');
        $this->middleware('permission:web-settings-update')->only('update');
    }

    public function index()
    {
        $page_data = get_option('manage-pages');
        return view('landing::admin.manage-pages', compact('page_data'));
    }

    public function update(Request $request, $key)
    {

        $option = Option::where('key', 'manage-pages')->first();

        Option::updateOrCreate(
            ['key' => 'manage-pages'],
            ['value' => [
                    'headings' => $request->except('_token', '_method', 'slider_image', 'contact_img', 'contact_us_icon', 'footer_socials_icons', 'watch_image', 'about_image', 'footer_image', 'payment_image', 'get_app_icon', 'get_apple_app_image', 'get_google_app_image'),

                    'watch_image' => $request->watch_image ? $this->upload($request, 'watch_image') : $option->value['watch_image'] ?? null,
                    'slider_image' => $request->slider_image ? $this->upload($request, 'slider_image') : $option->value['slider_image'] ?? null,
                    'contact_us_icon' => $request->contact_us_icon ? $this->upload($request, 'contact_us_icon') : $option->value['contact_us_icon'] ?? null,
                    'about_image' => $request->about_image ? $this->upload($request, 'about_image') : $option->value['about_image'] ?? null,
                    'footer_image' => $request->footer_image ? $this->upload($request, 'footer_image') : $option->value['footer_image'] ?? null,
                    'payment_image' => $request->payment_image ? $this->upload($request, 'payment_image') : $option->value['payment_image'] ?? null,
                    'get_app_icon' => $request->get_app_icon ? $this->upload($request, 'get_app_icon') : $option->value['get_app_icon'] ?? null,
                    'get_apple_app_image' => $request->get_apple_app_image ? $this->upload($request, 'get_apple_app_image') : $option->value['get_apple_app_image'] ?? null,
                    'get_google_app_image' => $request->get_google_app_image ? $this->upload($request, 'get_google_app_image') : $option->value['get_google_app_image'] ?? null,

                    'footer_socials_icons' => $request->footer_socials_icons ? $this->multipleUpload($request, 'footer_socials_icons') : $option->value['footer_socials_icons'] ?? null,
                ]
        ]);

        Cache::forget('manage-pages');
        return response()->json(__('Pages updated successfully.'));
    }
}
