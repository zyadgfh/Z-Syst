<?php

namespace App\Http\Controllers\Admin;

use App\Models\Prescription;
use App\Models\Party;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Exceptions\TransactionException;
use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class AcnooPrescriptionController extends Controller
{
    use HasUploader;

    public function __construct()
    {
        $this->middleware('permission:prescriptions-create')->only('create', 'store');
        $this->middleware('permission:prescriptions-read')->only('index');
        $this->middleware('permission:prescriptions-update')->only('edit', 'update', 'status');
        $this->middleware('permission:prescriptions-delete')->only('destroy', 'deleteAll');
    }

    public function index(Request $request)
    {
        $prescriptions = Prescription::with(['party:id,name,phone', 'sale:id,invoiceNumber'])
                            ->latest()
                            ->paginate(10);

        $parties = Party::where('business_id', auth()->user()->business_id ?? null)
                    ->select('id', 'name', 'phone')
                    ->get();

        return view('admin.prescriptions.index', compact('prescriptions', 'parties'));
    }

    public function acnooFilter(Request $request)
    {
        $prescriptions = Prescription::with(['party:id,name,phone', 'sale:id,invoiceNumber'])
                            ->when(request('search'), function ($q) {
                                $q->where(function ($q) {
                                    $q->where('notes', 'like', '%' . request('search') . '%')
                                        ->orWhere('status', 'like', '%' . request('search') . '%')
                                        ->orWhereHas('party', function ($query) {
                                            $query->where('name', 'like', '%' . request('search') . '%')
                                                ->orWhere('phone', 'like', '%' . request('search') . '%');
                                        })
                                        ->orWhereHas('sale', function ($query) {
                                            $query->where('invoiceNumber', 'like', '%' . request('search') . '%');
                                        });
                                });
                            })
                            ->latest()
                            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.prescriptions.datas', compact('prescriptions'))->render()
            ]);
        }

        return redirect(url()->previous());
    }

    public function store(Request $request)
    {
        $request->validate([
            'party_id' => 'nullable|exists:parties,id',
            'notes' => 'nullable|string|max:1000',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'status' => 'nullable|in:pending,used',
        ]);

        try {
            Prescription::create([
                'business_id' => auth()->user()->business_id ?? null,
                'party_id' => $request->party_id,
                'notes' => $request->notes,
                'image' => $request->image ? $this->upload($request, 'image') : null,
                'status' => $request->status ?? 'pending',
                'meta' => [
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now()->toDateTimeString(),
                ],
            ]);

            return response()->json([
                'message' => __('Prescription saved successfully'),
                'redirect' => route('admin.prescriptions.index')
            ]);
        } catch (\App\Exceptions\UploadException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode->value,
            ], 422);
        }
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'party_id' => 'nullable|exists:parties,id',
            'notes' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'status' => 'nullable|in:pending,used',
        ]);

        $prescription = Prescription::findOrFail($id);

        try {
            $prescription->update([
                'party_id' => $request->party_id,
                'notes' => $request->notes,
                'image' => $request->image ? $this->upload($request, 'image', $prescription->image) : $prescription->image,
                'status' => $request->status ?? $prescription->status,
            ]);

            return response()->json([
                'message' => __('Prescription updated successfully'),
                'redirect' => route('admin.prescriptions.index')
            ]);
        } catch (\App\Exceptions\UploadException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode->value,
            ], 422);
        }
    }

    public function destroy(string $id)
    {
        $prescription = Prescription::findOrFail($id);

        if (file_exists($prescription->image)) {
            Storage::delete($prescription->image);
        }

        $prescription->delete();

        return response()->json([
            'message' => __('Prescription deleted successfully'),
            'redirect' => route('admin.prescriptions.index')
        ]);
    }

    public function status(Request $request, $id)
    {
        $prescription = Prescription::findOrFail($id);
        $prescription->update(['status' => $request->status]);
        return response()->json(['message' => __('Status updated successfully')]);
    }

    public function deleteAll(Request $request)
    {
        $idsToDelete = $request->input('ids');

        if (!$idsToDelete || !is_array($idsToDelete)) {
            throw new BusinessRuleException(
                ErrorCode::VALIDATION_MISSING_FIELD,
                __('validation.required', ['attribute' => 'ids']),
                ['errors' => ['ids' => [__('validation.required', ['attribute' => 'ids'])]]]
            );
        }

        DB::beginTransaction();
        try {
            $prescriptions = Prescription::whereIn('id', $idsToDelete)->get();
            foreach ($prescriptions as $prescription) {
                if (file_exists($prescription->image)) {
                    Storage::delete($prescription->image);
                }
            }

            Prescription::whereIn('id', $idsToDelete)->delete();

            DB::commit();

            return response()->json([
                'message' => __('Selected prescriptions deleted successfully'),
                'redirect' => route('admin.prescriptions.index')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new TransactionException(
                'prescription:delete-all',
                ['ids' => $idsToDelete, 'error' => $e->getMessage()],
                $e
            );
        }
    }

    public function linkToSale(Request $request, $id)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
        ]);

        $prescription = Prescription::findOrFail($id);
        $prescription->update([
            'sale_id' => $request->sale_id,
            'status' => 'used',
        ]);

        return response()->json([
            'message' => __('Prescription linked to sale successfully'),
            'redirect' => route('admin.prescriptions.index')
        ]);
    }
}

