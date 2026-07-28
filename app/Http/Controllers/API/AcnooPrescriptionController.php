<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\Prescription;
use Illuminate\Http\Request;
use App\Helpers\HasUploader;
use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;

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
                ->where('business_id', auth()->user()->business_id)
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
        ]);

        $prescription = TransactionHelper::run(function () use ($request) {
            return Prescription::create([
                'business_id' => auth()->user()->business_id,
                'party_id' => $request->party_id,
                'notes' => $request->notes,
                'image' => $this->upload($request, 'image'),
                'status' => 'pending',
                'meta' => [
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now()->toDateTimeString(),
                ],
            ]);
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
        ]);

        $prescription = TransactionHelper::run(function () use ($request, $id) {
            $prescription = Prescription::findOrFail($id);

            $prescription->update([
                'party_id' => $request->party_id ?? $prescription->party_id,
                'notes' => $request->notes ?? $prescription->notes,
                'image' => $request->image ? $this->upload($request, 'image', $prescription->image) : $prescription->image,
            ]);

            return $prescription->fresh()->load(['party:id,name,phone']);
        }, 'prescription:update', ['prescription_id' => $id]);

        return response()->json([
            'message' => __('Prescription updated successfully.'),
            'data' => $prescription,
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
     * Link a prescription to a sale.
     */
    public function linkToSale(Request $request)
    {
        $request->validate([
            'prescription_id' => 'required|exists:prescriptions,id',
            'sale_id' => 'required|exists:sales,id',
        ]);

        $prescription = TransactionHelper::run(function () use ($request) {
            $prescription = Prescription::findOrFail($request->prescription_id);
            $prescription->update([
                'sale_id' => $request->sale_id,
                'status' => 'used',
            ]);

            return $prescription->load(['party:id,name,phone', 'sale:id,invoiceNumber']);
        }, 'prescription:link-to-sale', [
            'prescription_id' => $request->prescription_id,
            'sale_id' => $request->sale_id,
        ]);

        return response()->json([
            'message' => __('Prescription linked to sale successfully.'),
            'data' => $prescription,
        ]);
    }
}

