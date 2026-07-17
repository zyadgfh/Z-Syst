<?php

namespace Modules\ZSyst\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ZSyst\App\Models\Drug;
use Modules\ZSyst\App\Models\InventoryMovement;
use Modules\ZSyst\App\Models\InventoryItem;
use Modules\ZSyst\App\Models\PosSale;
use Modules\ZSyst\App\Models\PosSaleItem;
use App\Services\WhatsAppService;
use App\Services\InvoiceImageService;

class PosSaleController
{
    protected $whatsappService;
    protected $invoiceImageService;

    public function __construct(WhatsAppService $whatsappService, InvoiceImageService $invoiceImageService)
    {
        $this->whatsappService = $whatsappService;
        $this->invoiceImageService = $invoiceImageService;
    }

    public function index(): JsonResponse
    {
        return response()->json(PosSale::with('saleItems')->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'send_whatsapp' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:50'],
            'subtotal' => ['nullable', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'total_amount' => ['nullable', 'numeric'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
        ]);

        return DB::transaction(function () use ($request, $data) {
            // Create the sale
            $sale = PosSale::create($data + ['status' => $data['status'] ?? 'completed']);

            // Process items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    // Find drug by barcode
                    $drug = Drug::where('barcode', $item['barcode'])->first();
                    
                    // Create sale item
                    $saleItem = PosSaleItem::create([
                        'pos_sale_id' => $sale->id,
                        'drug_id' => $drug ? $drug->id : null,
                        'barcode' => $item['barcode'],
                        'name' => $item['name'],
                        'unit_price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'line_total' => $item['price'] * $item['quantity'],
                        'prescription_id' => $item['prescription_id'] ?? null,
                    ]);

                    // Update inventory if drug exists
                    if ($drug) {
                        $this->updateInventoryForSale($drug, $item['quantity'], $sale->id);
                    }
                }
            }

            // Load relationships for response
            $sale->load('saleItems');

            // إذا كان هناك خيار إرسال الواتساب ورقم هاتف العميل
            if ($request->boolean('send_whatsapp') && !empty($data['customer_phone'])) {
                $imageBase64 = $this->invoiceImageService->generateInvoiceImage($sale);
                $this->whatsappService->sendInvoice(
                    ['phone' => $data['customer_phone']],
                    $imageBase64,
                    $sale->id
                );
            }

            return response()->json($sale, 201);
        });
    }

    protected function updateInventoryForSale(Drug $drug, int $quantity, int $saleId): void
    {
        // Find inventory items (using FIFO - first in, first out)
        
        // Also send WhatsApp notification for inventory issues
        $inventoryItems = InventoryItem::where('drug_id', $drug->id)
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('expiry_date')
            ->get();

        $remainingQuantity = $quantity;

        foreach ($inventoryItems as $inventoryItem) {
            if ($remainingQuantity <= 0) break;

            $deductQuantity = min($inventoryItem->quantity_on_hand, $remainingQuantity);
            
            // Update inventory item
            $inventoryItem->quantity_on_hand -= $deductQuantity;
            $inventoryItem->save();

            // Create inventory movement record
            InventoryMovement::create([
                'drug_id' => $drug->id,
                'type' => 'out',
                'quantity' => $deductQuantity,
                'unit_cost' => $inventoryItem->unit_cost,
                'notes' => "Sale #{$saleId}",
            ]);

            $remainingQuantity -= $deductQuantity;
        }

        // If we couldn't deduct enough quantity, this is a stock issue
        if ($remainingQuantity > 0) {
            // Log this issue - in production you might want to handle this differently
            \Log::warning("Insufficient stock for drug {$drug->id} (barcode: {$drug->barcode}). Short by {$remainingQuantity} units.");
        }
    }

    public function show($id): JsonResponse
    {
        $sale = PosSale::with('saleItems')->findOrFail($id);
        return response()->json($sale);
    }

    public function validateInventory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.barcode' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $validationResults = [];
        $allAvailable = true;

        foreach ($data['items'] as $item) {
            $drug = Drug::where('barcode', $item['barcode'])->first();
            
            if (!$drug) {
                $validationResults[] = [
                    'barcode' => $item['barcode'],
                    'available' => false,
                    'reason' => 'Product not found',
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => 0,
                ];
                $allAvailable = false;
                continue;
            }

            $currentStock = $drug->current_stock;
            $isAvailable = $drug->isAvailable($item['quantity']);

            $validationResults[] = [
                'barcode' => $item['barcode'],
                'name' => $drug->name,
                'available' => $isAvailable,
                'reason' => $isAvailable ? 'In stock' : 'Insufficient stock',
                'requested_quantity' => $item['quantity'],
                'available_quantity' => $currentStock,
            ];

            if (!$isAvailable) {
                $allAvailable = false;
            }
        }

        return response()->json([
            'all_available' => $allAvailable,
            'items' => $validationResults,
        ]);
    }
}
