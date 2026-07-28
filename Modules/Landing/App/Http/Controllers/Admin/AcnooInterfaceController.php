<?php

namespace Modules\Landing\App\Http\Controllers\Admin;

use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Modules\Landing\App\Exports\ExportInterface;
use Modules\Landing\App\Models\PosAppInterface;

class AcnooInterfaceController extends Controller
{
    use HasUploader;

    public function __construct()
    {
        $this->middleware('permission:interfaces-read')->only('index');
        $this->middleware('permission:interfaces-create')->only('create', 'store');
        $this->middleware('permission:interfaces-update')->only('edit', 'update','status');
        $this->middleware('permission:interfaces-delete')->only('destroy','deleteAll');
    }

    public function index(Request $request)
    {
        $interfaces = PosAppInterface::latest()->paginate(10);
        return view('landing::admin.interfaces.index', compact('interfaces'));
    }

    public function acnooFilter(Request $request)
    {
        $interfaces = PosAppInterface::latest()->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('landing::admin.interfaces.datas', compact('interfaces'))->render()
            ]);
        }

        return redirect(url()->previous());
    }

    public function create()
    {
        return view('landing::admin.interfaces.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'status' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        PosAppInterface::create($request->except('image') + [
            'image' => $request->image ? $this->upload($request, 'image') : NULL
        ]);

        return response()->json([
            'message' => __('Interfaces created successfully'),
            'redirect' => route('admin.interfaces.index')
        ]);
    }

    public function edit($id)
    {
        $interface = PosAppInterface::findOrFail($id);
        return view('landing::admin.interfaces.edit', compact('interface'));
    }

    public function update(Request $request,  $id)
    {
        $interface = PosAppInterface::findOrFail($id);
        $request->validate([
            'status' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $interface->update($request->except('image') + [
            'image' => $request->image ? $this->upload($request, 'image', $interface->image) : $interface->image,
        ]);

        return response()->json([
            'message' => __('Interface updated successfully'),
            'redirect' => route('admin.interfaces.index')
        ]);
    }

    public function destroy($id)
    {
        $posAppInterface = PosAppInterface::findOrFail($id);
        if (file_exists($posAppInterface->image)) {
            Storage::delete($posAppInterface->image);
        }
        $posAppInterface->delete();

        return response()->json([
            'message'   => __('Interface deleted successfully'),
            'redirect'  => route('admin.interfaces.index')
        ]);
    }

    public function status(Request $request,$id)
    {
        $posAppInterface = PosAppInterface::findOrFail($id);
        $posAppInterface->update(['status' => $request->status]);
        return response()->json(['message' => 'Interface ']);
    }

    public function deleteAll(Request $request)
    {
        $posAppInterfaces = PosAppInterface::whereIn('id', $request->ids)->get();
        foreach ($posAppInterfaces as $posAppInterface) {
            if (file_exists($posAppInterface->image)) {
                Storage::delete($posAppInterface->image);
            }
        }

        $posAppInterfaces->each->delete();

        return response()->json([
            'message' => __('Selected Interface deleted successfully'),
            'redirect' => route('admin.interfaces.index')
        ]);
    }

    public function exportExcel()
    {
        return Excel::download(new ExportInterface, 'interfaces.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportInterface, 'interfaces.csv');
    }
}
