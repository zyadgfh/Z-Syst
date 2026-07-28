<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\Prescription;
use App\Models\User;
use App\Notifications\SendNotification;
use Illuminate\Http\Request;
use App\Helpers\HasUploader;
use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcnooPrescriptionController extends Controller
{
    use HasUploader;

    /**
     * Display a listing of the prescriptions.
     */
    public function index()
    {
        $data = Prescription::select('id', 'business_id', 'sale_id', 'party_id', 'image', 'notes', 'status', 'created_at')
                ->with(['party:id,name,phone', 'sale:id,invoiceNumber'])
                ->where('business_id', Auth::user()?->business_id)
                ->when(request('search'), function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->where('notes', 'like', '%' . request('search') . '%')
                            ->orWhere('status', 'like', '%' . request('search') . '%')
                            ->orWhereHas('party', function ($q) {
                                $q->where('name', 'like', '%' . request('search') . '%')
                                    ->orWhere('phone', 'like', '%' . request('search') . '%');
                            })
                            ->orWhereHas('sale', function ($q) {
                                $q->where('invoiceNumber', 'like', '%' . request('search') . '%');
                            });
                    });
                })
                ->latest()
                ->paginate(10);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created prescription.
     */
    public function store(Request $request)
    {
        $request->validate([
            'party_id' => 'nullable|exists:parties,id',
            'notes' => 'nullable|string|max:1000',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
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

        $prescription = TransactionHelper::run(function () use ($request) {
            $prescription = Prescription::create([
                'business_id' => Auth::user()?->business_id,
                'party_id' => $request->party_id,
                'notes' => $request->notes,
                'image' => $this->upload($request, 'image'),
                'status' => 'pending',
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

            return $prescription;
        }, 'prescription:store', ['party_id' => $request->party_id]);

        return response()->json([
            'message' => __('Prescription saved successfully.'),
            'data' => $prescription->load(['party:id,name,phone']),
        ]);
    }

    /**
     * Display the specified prescription.
     */
    public function show($id)
    {
        $data = Prescription::with([
                    'party:id,name,phone,address',
                    'sale:id,invoiceNumber,totalAmount,saleDate',
                    'sale.details:id,sale_id,product_id,price,quantities',
                    'sale.details.product:id,productName',
                ])
                ->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Update the specified prescription.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'party_id' => 'nullable|exists:parties,id',
            'notes' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
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

        $prescription = TransactionHelper::run(function () use ($request, $id) {
            $prescription = Prescription::findOrFail($id);
            $reviewStatus = $request->review_status ?? $prescription->review_status ?? 'pending';
            $meta = (array) ($prescription->meta ?? []);

            if ($request->has('batch_no')) {
                $meta['batch_no'] = $request->batch_no;
            }

            if ($request->has('expiry_date')) {
                $meta['expiry_date'] = $request->expiry_date;
            }

            $payload = [
                'party_id' => $request->party_id ?? $prescription->party_id,
                'notes' => $request->notes ?? $prescription->notes,
                'image' => $request->image ? $this->upload($request, 'image', $prescription->image) : $prescription->image,
                'prescription_number' => $request->prescription_number ?? $prescription->prescription_number,
                'review_status' => $reviewStatus,
                'review_notes' => $request->review_notes ?? $prescription->review_notes,
                'expires_at' => $request->expires_at ?? $prescription->expires_at,
                'patient_name' => $request->patient_name ?? $prescription->patient_name,
                'patient_phone' => $request->patient_phone ?? $prescription->patient_phone,
                'doctor_name' => $request->doctor_name ?? $prescription->doctor_name,
                'doctor_license' => $request->doctor_license ?? $prescription->doctor_license,
                'meta' => $meta,
            ];

            if ($reviewStatus !== $prescription->review_status) {
                $payload['reviewed_by'] = Auth::id();
                $payload['reviewed_at'] = now();
            }

            $prescription->update($payload);
            $this->notifyIfExpiringSoon($prescription->fresh());

            return $prescription->fresh()->load(['party:id,name,phone']);
        }, 'prescription:update', ['prescription_id' => $id]);

        return response()->json([
            'message' => __('Prescription updated successfully.'),
            'data' => $prescription,
            'expiry_alerts' => $this->buildExpiryAlertSummary(),
        ]);
    }

    /**
     * Remove the specified prescription.
     */
    public function destroy($id)
    {
        $prescription = Prescription::findOrFail($id);

        if (file_exists($prescription->image)) {
            \Illuminate\Support\Facades\Storage::delete($prescription->image);
        }

        $prescription->delete();

        return response()->json([
            'message' => __('Prescription deleted successfully.'),
        ]);
    }

    /**
     * List pending and approved prescriptions only.
     */
    public function review(Request $request)
    {
        $businessId = Auth::user()?->business_id;
        $query = Prescription::with(['party:id,name,phone', 'sale:id,invoiceNumber'])
            ->where('business_id', $businessId)
            ->whereIn('review_status', ['pending', 'approved']);

        if ($request->filled('review_status')) {
            $query->where('review_status', $request->review_status);
        }

        $data = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
            'expiry_alerts' => $this->buildExpiryAlertSummary(),
        ]);
    }

    /**
     * Link a prescription to a sale.
     */
    public function linkToSale(Request $request)
    {
        $request->validate([
            'prescription_id' => 'required|exists:prescriptions,id',
            'sale_id' => 'required|exists:sales,id',
            'batch_no' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
        ]);

        $prescription = TransactionHelper::run(function () use ($request) {
            $prescription = Prescription::findOrFail($request->prescription_id);

            if (!$prescription->canBeUsed()) {
                throw ValidationException::withMessages([
                    'prescription_id' => [__('The prescription must be approved and not expired before it can be used.')],
                ]);
            }

            $meta = (array) ($prescription->meta ?? []);
            if ($request->has('batch_no')) {
                $meta['batch_no'] = $request->batch_no;
            }
            if ($request->has('expiry_date')) {
                $meta['expiry_date'] = $request->expiry_date;
            }

            $prescription->update([
                'sale_id' => $request->sale_id,
                'status' => 'used',
                'used_at' => now(),
                'meta' => $meta,
            ]);

            return $prescription->load(['party:id,name,phone', 'sale:id,invoiceNumber']);
        }, 'prescription:link-to-sale', [
            'prescription_id' => $request->prescription_id,
            'sale_id' => $request->sale_id,
        ]);

        return response()->json([
            'message' => __('Prescription linked to sale successfully.'),
            'data' => $prescription,
            'expiry_alerts' => $this->buildExpiryAlertSummary(),
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

