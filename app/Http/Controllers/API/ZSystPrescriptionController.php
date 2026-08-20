<?php

namespace App\Http\Controllers\Api;

use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ZSystPrescriptionController extends Controller
{
    use HasUploader;

    public function __construct(
        private PrescriptionService $prescriptionService
    ) {}

    /**
     * Display a listing of the prescriptions.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $data = Prescription::select('id', 'business_id', 'sale_id', 'party_id', 'patient_id', 'doctor_id', 'image', 'notes', 'status', 'created_at')
            ->with([
                'party:id,name,phone', 
                'sale:id,invoiceNumber',
                'patient:id,name,national_id',
                'doctor:id,name,specialization'
            ])
            ->where('business_id', Auth::user()?->business_id)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('notes', 'like', '%'.$search.'%')
                        ->orWhere('status', 'like', '%'.$search.'%')
                        ->orWhere('patient_name', 'like', '%'.$search.'%')
                        ->orWhere('doctor_name', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate($request->input('per_page', 10));

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
            'patient_id' => 'nullable|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'notes' => 'nullable|string|max:1000',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'prescription_number' => 'nullable|string|max:50',
            'expires_at' => 'nullable|date',
            'patient_name' => 'nullable|string|max:255',
            'patient_phone' => 'nullable|string|max:20',
            'doctor_name' => 'nullable|string|max:255',
            'doctor_license' => 'nullable|string|max:100',
            'batch_no' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.dosage' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.instructions' => 'nullable|string|max:1000',
        ]);

        try {
            $data = $request->all();
            $data['image'] = $this->upload($request, 'image');

            if ($request->has('batch_no')) {
                $data['meta']['batch_no'] = $request->batch_no;
            }
            if ($request->has('expiry_date')) {
                $data['meta']['expiry_date'] = $request->expiry_date;
            }

            $prescription = $this->prescriptionService->createPrescription($data, Auth::user()->business_id);

            return response()->json([
                'message' => __('Prescription saved successfully.'),
                'data' => $prescription->load(['party:id,name,phone', 'patient:id,name', 'doctor:id,name']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error saving prescription.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified prescription.
     */
    public function show($id)
    {
        $data = Prescription::with([
            'party:id,name,phone,address',
            'patient:id,name,national_id,date_of_birth',
            'doctor:id,name,specialization,license_number',
            'sale:id,invoiceNumber,totalAmount,saleDate',
            'sale.details:id,sale_id,product_id,price,quantities',
            'sale.details.product:id,productName',
            'items',
            'items.product:id,productName'
        ])->findOrFail($id);

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
            'patient_id' => 'nullable|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
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
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.dosage' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.instructions' => 'nullable|string|max:1000',
        ]);

        try {
            $prescription = Prescription::findOrFail($id);
            $data = $request->all();

            if ($request->hasFile('image')) {
                $data['image'] = $this->upload($request, 'image', $prescription->image);
            }

            if ($request->has('batch_no')) {
                $data['meta']['batch_no'] = $request->batch_no;
            }
            if ($request->has('expiry_date')) {
                $data['meta']['expiry_date'] = $request->expiry_date;
            }

            $updatedPrescription = $this->prescriptionService->updatePrescription($prescription, $data);

            return response()->json([
                'message' => __('Prescription updated successfully.'),
                'data' => $updatedPrescription,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error updating prescription.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified prescription.
     */
    public function destroy($id)
    {
        $prescription = Prescription::findOrFail($id);
        
        try {
            $this->prescriptionService->deletePrescription($prescription);

            return response()->json([
                'message' => __('Prescription deleted successfully.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error deleting prescription.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List pending and approved prescriptions only.
     */
    public function review(Request $request)
    {
        $businessId = Auth::user()?->business_id;
        $query = Prescription::with(['party:id,name,phone', 'sale:id,invoiceNumber', 'patient:id,name', 'doctor:id,name'])
            ->where('business_id', $businessId)
            ->whereIn('review_status', ['pending', 'approved']);

        if ($request->filled('review_status')) {
            $query->where('review_status', $request->review_status);
        }

        $data = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
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

        try {
            $prescription = Prescription::findOrFail($request->prescription_id);
            $data = $request->only(['batch_no', 'expiry_date']);
            
            $prescription->load('sale');
            if (!$prescription->sale) {
                 // The old method was in the controller, but the service one requires a sale object, not just ID. 
                 // Wait, linkToSale in PrescriptionService: public function linkToSale(Prescription $prescription, Sale $sale): Prescription
            }
            
            // To match the old behaviour where linkToSale could update using Sale ID.
            $sale = \App\Models\Sale::findOrFail($request->sale_id);

            $linkedPrescription = $this->prescriptionService->linkToSale($prescription, $sale);

            return response()->json([
                'message' => __('Prescription linked to sale successfully.'),
                'data' => $linkedPrescription,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error linking prescription to sale.'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
