<?php

namespace App\Http\Controllers\Admin;

use App\Models\Prescription;
use App\Models\Party;
use App\Models\User;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Exceptions\TransactionException;
use App\Helpers\HasUploader;
use App\Notifications\SendNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ZSystPrescriptionController extends Controller
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

        $parties = Party::where('business_id', Auth::user()?->business_id ?? null)
                    ->select('id', 'name', 'phone')
                    ->get();

        $expiryAlertSummary = $this->buildExpiryAlertSummary();

        return view('admin.prescriptions.index', compact('prescriptions', 'parties', 'expiryAlertSummary'));
    }

    public function zsystFilter(Request $request)
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
            'prescription_number' => 'nullable|string|max:50',
            'review_status' => 'nullable|in:pending,approved,rejected',
            'review_notes' => 'nullable|string|max:2000',
            'expires_at' => 'nullable|date',
            'patient_name' => 'nullable|string|max:255',
            'patient_phone' => 'nullable|string|max:20',
            'doctor_name' => 'nullable|string|max:255',
            'doctor_license' => 'nullable|string|max:100',
            'batch_no' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
        ]);

        try {
            $prescription = Prescription::create([
                'business_id' => Auth::user()?->business_id ?? null,
                'party_id' => $request->party_id,
                'notes' => $request->notes,
                'image' => $request->image ? $this->upload($request, 'image') : null,
                'status' => $request->status ?? 'pending',
                'prescription_number' => $request->prescription_number ?? 'RX-' . Str::upper(Str::random(6)),
                'review_status' => $request->review_status ?? 'pending',
                'review_notes' => $request->review_notes,
                'expires_at' => $request->expires_at,
                'patient_name' => $request->patient_name,
                'patient_phone' => $request->patient_phone,
                'doctor_name' => $request->doctor_name,
                'doctor_license' => $request->doctor_license,
                'meta' => [
                    'uploaded_by' => Auth::id(),
                    'uploaded_at' => now()->toDateTimeString(),
                    'batch_no' => $request->batch_no,
                    'expiry_date' => $request->expiry_date,
                ],
            ]);

            $this->notifyIfExpiringSoon($prescription);

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
            'prescription_number' => 'nullable|string|max:50',
            'review_status' => 'nullable|in:pending,approved,rejected',
            'review_notes' => 'nullable|string|max:2000',
            'expires_at' => 'nullable|date',
            'patient_name' => 'nullable|string|max:255',
            'patient_phone' => 'nullable|string|max:20',
            'doctor_name' => 'nullable|string|max:255',
            'doctor_license' => 'nullable|string|max:100',
            'batch_no' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
        ]);

        $prescription = Prescription::findOrFail($id);

        try {
            $reviewStatus = $request->review_status ?? $prescription->review_status ?? 'pending';
            $meta = (array) ($prescription->meta ?? []);

            if ($request->has('batch_no')) {
                $meta['batch_no'] = $request->batch_no;
            }

            if ($request->has('expiry_date')) {
                $meta['expiry_date'] = $request->expiry_date;
            }

            $prescription->update([
                'party_id' => $request->party_id ?? $prescription->party_id,
                'notes' => $request->notes ?? $prescription->notes,
                'image' => $request->image ? $this->upload($request, 'image', $prescription->image) : $prescription->image,
                'status' => $request->status ?? $prescription->status,
                'prescription_number' => $request->prescription_number ?? $prescription->prescription_number,
                'review_status' => $reviewStatus,
                'review_notes' => $request->review_notes ?? $prescription->review_notes,
                'expires_at' => $request->expires_at ?? $prescription->expires_at,
                'patient_name' => $request->patient_name ?? $prescription->patient_name,
                'patient_phone' => $request->patient_phone ?? $prescription->patient_phone,
                'doctor_name' => $request->doctor_name ?? $prescription->doctor_name,
                'doctor_license' => $request->doctor_license ?? $prescription->doctor_license,
                'meta' => $meta,
            ]);

            $this->notifyIfExpiringSoon($prescription->fresh());

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

    protected function notifyIfExpiringSoon(Prescription $prescription): void
    {
        if (empty($prescription->expires_at)) {
            return;
        }

        $status = $prescription->getExpiryStatus();
        if (!in_array($status, ['warning', 'critical', 'expired'], true)) {
            return;
        }

        $businessId = $prescription->business_id;
        $users = User::where('business_id', $businessId)->get();

        if ($users->isEmpty()) {
            return;
        }

        $days = $prescription->isExpired()
            ? 0
            : (int) now()->startOfDay()->diffInDays($prescription->expires_at, false);

        $label = $prescription->isExpired()
            ? __('expired')
            : __('expires in :days day(s)', ['days' => $days]);

        $message = __('Prescription :number is :label and needs review.', [
            'number' => $prescription->prescription_number ?? $prescription->id,
            'label' => $label,
        ]);

        Notification::send($users, new SendNotification([
            'id' => uniqid(),
            'user' => Auth::user()?->name ?? 'System',
            'message' => $message,
            'url' => '/admin/prescriptions',
        ]));
    }

    protected function buildExpiryAlertSummary(): array
    {
        $businessId = Auth::user()?->business_id;

        if (!$businessId) {
            return [
                'expired' => 0,
                'critical' => 0,
                'warning' => 0,
                'total' => 0,
            ];
        }

        $items = Prescription::where('business_id', $businessId)
            ->whereNotNull('expires_at')
            ->get();

        $summary = [
            'expired' => 0,
            'critical' => 0,
            'warning' => 0,
            'total' => $items->count(),
        ];

        foreach ($items as $item) {
            $status = $item->getExpiryStatus();
            if ($status !== 'none' && isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        return $summary;
    }
}

