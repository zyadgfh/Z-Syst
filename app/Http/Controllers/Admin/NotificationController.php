<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        $notifications = Notification::whereDate('created_at', today())->latest()->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.notifications.datas', compact('notifications'))->render(),
            ]);
        }

        return redirect(url()->previous());
    }

    public function mtView($id)
    {
        $notify = Notification::find($id);
        if ($notify) {
            $notify->read_at = now();
            $notify->save();

            // Open redirect protection: only allow local paths
            $url = $notify->data['url'] ?? '/';
            if (
                Str::startsWith($url, ['http://', 'https://'])
                && !Str::startsWith($url, url('/'))
            ) {
                $url = '/'; // Block external redirects
            }

            return redirect($url);
        }

        return back()->with('error', __('Permission denied.'));
    }

    public function mtReadAll()
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
