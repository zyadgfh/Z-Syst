<?php

namespace Modules\Landing\App\Http\Controllers\Admin;

use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Landing\App\Exports\ExportMessage;
use Modules\Landing\App\Models\Message;

class AcnooMessageController extends Controller
{
    use HasUploader;

    public function __construct()
    {
        $this->middleware('permission:messages-read')->only('index');
        $this->middleware('permission:messages-delete')->only('destroy','deleteAll');
    }


    public function index(Request $request)
    {
        $messages = Message::latest()->paginate(10);
        return view('landing::admin.messages.index', compact('messages'));
    }

    public function acnooFilter(Request $request)
    {
        $messages = Message::when(request('search'), function ($q) {
            $q->where(function ($q) {
                $q->where('name', 'like', '%' . request('search') . '%')
                    ->orWhere('phone', 'like', '%' . request('search') . '%')
                    ->orWhere('company_name', 'like', '%' . request('search') . '%')
                    ->orWhere('message', 'like', '%' . request('search') . '%')
                    ->orWhere('email', 'like', '%' . request('search') . '%');
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('landing::admin.messages.datas', compact('messages'))->render()
            ]);
        }

        return redirect(url()->previous());
    }

    public function destroy($id)
    {
        $message = Message::findOrFail($id);

        $message->delete();

        return response()->json([
            'message'   => __('Message deleted successfully'),
            'redirect'  => route('admin.messages.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
        $messages = Message::whereIn('id', $request->ids)->get();

        $messages->each->delete();

        return response()->json([
            'message' => __('Selected Messages deleted successfully'),
            'redirect' => route('admin.messages.index')
        ]);
    }

    public function exportExcel()
    {
        return Excel::download(new ExportMessage, 'messages.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportMessage, 'messages.csv');
    }
}
