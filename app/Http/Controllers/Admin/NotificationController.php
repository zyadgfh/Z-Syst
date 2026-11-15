<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:notifications-read')->only('mtIndex');
    }

    public function mtIndex()
    {
        $notifications = auth()->user()->notifications()
            ->whereDate('created_at', today())
            ->latest()
            ->get();

        return view('admin.notifications.index', compact('notifications'));
    }

    public function zsystFilter(Request $request)
    {
        $notifications = auth()->user()->notifications()->whereDate('created_at', today())->latest()->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.notifications.datas', compact('notifications'))->render(),
            ]);
        }

        return redirect(url()->previous());
    }

    public function mtView($id)
    {
        $notify = auth()->user()->notifications()->findOrFail($id);
        $notify->markAsRead();

        // Permit only local absolute paths, never protocol-relative or backslash URLs.
        $url = $notify->data['url'] ?? '/';
        if (! is_string($url) || ! str_starts_with($url, '/') || str_starts_with($url, '//')
            || str_contains($url, chr(92)) || preg_match('/[\x00-\x1f\x7f]/', $url)) {
            $url = '/';
        }

        return redirect($url);
    }

    public function mtReadAll()
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
