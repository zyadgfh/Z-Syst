<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrescriptionRequest;
use App\Http\Requests\DispensePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\Stock;
use App\Services\ForecastingService;
use App\Services\PrescriptionService;
use App\Services\Stock\StockAllocationService;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PrescriptionController extends Controller
{
    private readonly StockMovementService $stockMovementService;

    public function __construct(
        private readonly PrescriptionService $prescriptionService,
        private readonly ForecastingService $forecastingService
    ) {
        $this->stockMovementService = app(StockMovementService::class);
    }

    public function index(Request $request): JsonResponse
    {
        $prescriptions = Prescription::where('company_id', $request->user()->company_id)
            ->with(['patient:id,name,phone', 'doctor:id,name,specialization', 'branch:id,name', 'items.product:id,productName'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->patient_id, fn($q, $v) => $q->where('patient_id', $v))
            ->when($request->doctor_id, fn($q, $v) => $q->where('doctor_id', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('prescribed_date', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('prescribed_date', '<=', $v))
            ->when($request->search, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('prescription_number', 'like', "%{$v}%")
                        ->orWhereHas('patient', fn($p) => $p->where('name', 'like', "%{$v}%"))
                        ->orWhereHas('doctor', fn($d) => $d->where('name', 'like', "%{$v}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($prescriptions);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'branch_id' => 'required|exists:branches,id',
            'prescribed_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:prescribed_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.dosage' => 'required|string|max:255',
            'items.*.frequency' => 'required|string|max:255',
            'items.*.duration' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.instructions' => 'nullable|string',
            'items.*.substitution_allowed' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();
        $items = $data['items'];
        unset($data['items']);

        $prescription = Prescription::create($data + [
            'company_id' => $request->user()->company_id,
            'prescription_number' => 'RX-' . strtoupper(uniqid()),
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        foreach ($items as $item) {
            $prescription->items()->create([
                'product_id' => $item['product_id'],
                'dosage' => $item['dosage'],
                'frequency' => $item['frequency'],
                'duration' => $item['duration'],
                'quantity' => $item['quantity'],
                'dispensed_quantity' => 0,
                'instructions' => $item['instructions'] ?? null,
                'substitution_allowed' => $item['substitution_allowed'] ?? true,
            ]);
        }

        return response()->json($prescription->load(['patient:id,name', 'doctor:id,name', 'items.product:id,productName']), 201);
    }

    public function show(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($prescription->load([
            'patient:id,name,phone,date_of_birth,gender,blood_group,allergies',
            'doctor:id,name,specialization,license_number,clinic_name,phone',
            'branch:id,name',
            'createdBy:id,name',
            'items.product:id,productName',
        ]));
    }

    public function update(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($prescription->status, ['pending', 'partially_dispensed'])) {
            return response()->json(['message' => 'Cannot modify a completed or cancelled prescription'], 422);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'sometimes|exists:patients,id',
            'doctor_id' => 'sometimes|exists:doctors,id',
            'branch_id' => 'sometimes|exists:branches,id',
            'expiry_date' => 'nullable|date|after_or_equal:prescribed_date',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,partially_dispensed,dispensed,cancelled,expired',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $prescription->update($validator->validated());

        return response()->json($prescription->fresh());
    }

    public function destroy(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($prescription->status, ['pending', 'cancelled', 'expired'])) {
            return response()->json(['message' => 'Cannot delete a dispensed prescription'], 422);
        }

        $prescription->items()->delete();
        $prescription->delete();

        return response()->json(['message' => 'Prescription deleted successfully']);
    }

    public function dispense(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($prescription->status === 'dispensed') {
            return response()->json(['message' => 'Prescription already fully dispensed'], 422);
        }

        if ($prescription->expiry_date && $prescription->expiry_date->isPast()) {
            return response()->json(['message' => 'Prescription has expired'], 422);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required_without:items.*.barcode|exists:prescription_items,id',
            'items.*.barcode' => 'required_without:items.*.id|string',
            'items.*.dispensed_quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $allFullyDispensed = true;
        foreach ($request->items as $itemData) {
            $item = null;

            if (!empty($itemData['id'])) {
                $item = $prescription->items()->findOrFail($itemData['id']);
            } else {
                $barcode = trim((string) ($itemData['barcode'] ?? ''));
                $product = Product::query()
                    ->where('company_id', $prescription->company_id)
                    ->where('barcode', $barcode)
                    ->first();

                if (! $product) {
                    return response()->json(['message' => 'Barcode not found'], 404);
                }

                $item = $prescription->items()->where('product_id', $product->id)->first();

                if (! $item) {
                    return response()->json(['message' => 'This barcode does not belong to a prescribed item'], 422);
                }
            }

            $item->update(['dispensed_quantity' => $itemData['dispensed_quantity']]);

            if ($itemData['dispensed_quantity'] < $item->quantity) {
                $allFullyDispensed = false;
            }
        }

        $prescription->update([
            'status' => $allFullyDispensed ? 'dispensed' : 'partially_dispensed',
        ]);

        return response()->json($prescription->fresh()->load(['items']));
    }

    public function dispenseByBarcode(Request $request, Prescription $prescription): JsonResponse
    {
        if ($prescription->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $product = Product::query()
            ->where('company_id', $prescription->company_id)
            ->where('barcode', $request->input('barcode'))
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Barcode not found'], 404);
        }

        $item = $prescription->items()->where('product_id', $product->id)->first();

        if (! $item) {
            return response()->json(['message' => 'This barcode does not belong to a prescribed item'], 422);
        }

        $newDispensed = min((int) $request->input('quantity'), (int) $item->quantity);
        $currentDispensed = (int) $item->dispensed_quantity;
        $updatedDispensed = min($item->quantity, $currentDispensed + $newDispensed);
        $item->update(['dispensed_quantity' => $updatedDispensed]);

        $stock = Stock::query()
            ->where('product_id', $product->id)
            ->whereHas('product', function ($query) use ($prescription) {
                $query->where('company_id', $prescription->company_id);
            })
            ->first();

        if ($stock) {
            StockAllocationService::allocateToProductStock(
                $product->id,
                $newDispensed,
                $prescription->branch_id
            );
        }

        $this->stockMovementService->logMovement(
            (int) $product->id,
            (int) $prescription->branch_id,
            'out',
            (float) $newDispensed,
            'prescription_dispense',
            (int) $prescription->id,
            null,
            [
                'prescription_number' => $prescription->prescription_number,
                'barcode' => $request->input('barcode'),
            ]
        );

        $allFullyDispensed = $prescription->items()
            ->whereColumn('dispensed_quantity', '<', 'quantity')
            ->doesntExist();

        $prescription->update([
            'status' => $allFullyDispensed ? 'dispensed' : 'partially_dispensed',
        ]);

        return response()->json($prescription->fresh()->load(['items']));
    }

    public function checkout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.barcode' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $companyId = $request->user()->company_id ?? app('tenant.company_id');
        $items = [];

        foreach ($request->input('items', []) as $entry) {
            $product = Product::query()
                ->where('company_id', $companyId)
                ->where('barcode', trim((string) ($entry['barcode'] ?? '')))
                ->first();

            if (! $product) {
                return response()->json(['message' => 'Barcode not found'], 404);
            }

            $stock = Stock::query()
                ->where('product_id', $product->id)
                ->whereHas('product', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->first();

            if (! $stock) {
                return response()->json(['message' => 'No stock record found for this product'], 404);
            }

            $quantity = (int) $entry['quantity'];
            if ($stock->productStock < $quantity) {
                return response()->json(['message' => 'Insufficient stock for this item'], 422);
            }

            StockAllocationService::allocateToProductStock(
                $product->id,
                $quantity,
                $request->input('branch_id', 1)
            );

            $this->stockMovementService->logMovement(
                (int) $product->id,
                (int) ($request->input('branch_id') ?? 1),
                'out',
                (float) $quantity,
                'pos_checkout',
                null,
                null,
                [
                    'barcode' => $product->barcode,
                    'checkout_source' => 'pos',
                ]
            );

            $items[] = [
                'product_id' => $product->id,
                'barcode' => $product->barcode,
                'name' => $product->productName,
                'quantity' => $quantity,
                'unit_price' => (float) ($product->sales_price ?? 0),
                'line_total' => (float) (($product->sales_price ?? 0) * $quantity),
            ];
        }

        return response()->json([
            'message' => 'Checkout completed successfully',
            'items' => $items,
            'total_items' => count($items),
            'generated_at' => now()->toDateTimeString(),
        ]);
    }

    public function demandForecast(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');
        $windowDays = (int) ($request->input('window_days') ?? 30);

        $forecast = $this->forecastingService->generateDemandForecast($companyId, $windowDays);

        return response()->json($forecast);
    }




    public function posSummary(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');

        $pendingPrescriptions = Prescription::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'partially_dispensed'])
            ->count();

        $lowStockProducts = Stock::query()
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->where('products.company_id', $companyId)
            ->whereColumn('stocks.productStock', '<=', 'products.alert_qty')
            ->select('stocks.*', 'products.alert_qty')
            ->with('product:id,productName')
            ->limit(10)
            ->get();

        $forecast = $this->demandForecast($request)->getData(true)['items'] ?? [];


        return response()->json([
            'pending_prescriptions_count' => $pendingPrescriptions,
            'low_stock_products' => $lowStockProducts->map(fn ($stock) => [
                'product_id' => $stock->product_id,
                'name' => $stock->product?->productName ?? 'Unknown',
                'stock' => (int) $stock->productStock,
                'alert_qty' => (int) ($stock->alert_qty ?? 0),
            ])->values(),
            'forecast' => $forecast,
            'generated_at' => now()->toDateTimeString(),
        ]);
    }
}
