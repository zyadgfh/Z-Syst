<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierPortalUserRequest;
use App\Http\Requests\SupplierOrderResponseRequest;
use App\Http\Requests\SupplierShipmentRequest;
use App\Http\Requests\SupplierInvoiceUploadRequest;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Services\SupplierPortalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupplierPortalController extends Controller
{
    protected SupplierPortalService $supplierPortalService;

    public function __construct(SupplierPortalService $supplierPortalService)
    {
        $this->supplierPortalService = $supplierPortalService;
    }

    /**
     * Create a new supplier portal user.
     */
    public function createUser(SupplierPortalUserRequest $request): JsonResponse
    {
        $user = $this->supplierPortalService->createSupplierUser(
            $request->validated(),
            auth()->user()->business_id
        );

        return response()->json([
            'message' => 'Supplier portal user created successfully',
            'data' => $user,
        ], 201);
    }

    /**
     * Get supplier dashboard.
     */
    public function getDashboard(Request $request, int $partyId): JsonResponse
    {
        $dashboard = $this->supplierPortalService->getSupplierDashboard(
            auth()->user()->business_id,
            $partyId
        );

        return response()->json([
            'data' => $dashboard,
        ]);
    }

    /**
     * Get pending orders for supplier.
     */
    public function getPendingOrders(Request $request, int $partyId): JsonResponse
    {
        $orders = $this->supplierPortalService->getPendingOrders(
            auth()->user()->business_id,
            $partyId
        );

        return response()->json([
            'data' => $orders,
        ]);
    }

    /**
     * Respond to a purchase order.
     */
    public function respondToOrder(SupplierOrderResponseRequest $request, int $orderId): JsonResponse
    {
        $purchaseOrder = PurchaseOrder::findOrFail($orderId);
        
        // Validate that the supplier belongs to this business
        if ($purchaseOrder->business_id !== auth()->user()->business_id) {
            return response()->json([
                'message' => 'Unauthorized access to this order',
            ], 403);
        }

        $response = $this->supplierPortalService->respondToOrder(
            $purchaseOrder,
            $request->validated(),
            auth()->id()
        );

        return response()->json([
            'message' => 'Order response recorded successfully',
            'data' => $response,
        ]);
    }

    /**
     * Create a shipment.
     */
    public function createShipment(SupplierShipmentRequest $request, int $orderId): JsonResponse
    {
        $purchaseOrder = PurchaseOrder::findOrFail($orderId);
        
        if ($purchaseOrder->business_id !== auth()->user()->business_id) {
            return response()->json([
                'message' => 'Unauthorized access to this order',
            ], 403);
        }

        $shipment = $this->supplierPortalService->createShipment(
            $purchaseOrder,
            $request->validated(),
            auth()->id()
        );

        return response()->json([
            'message' => 'Shipment created successfully',
            'data' => $shipment,
        ], 201);
    }

    /**
     * Upload supplier invoice.
     */
    public function uploadInvoice(SupplierInvoiceUploadRequest $request, int $orderId): JsonResponse
    {
        $purchaseOrder = PurchaseOrder::findOrFail($orderId);
        
        if ($purchaseOrder->business_id !== auth()->user()->business_id) {
            return response()->json([
                'message' => 'Unauthorized access to this order',
            ], 403);
        }

        $invoice = $this->supplierPortalService->uploadInvoice(
            $purchaseOrder,
            $request->validated(),
            auth()->id()
        );

        return response()->json([
            'message' => 'Invoice uploaded successfully',
            'data' => $invoice,
        ], 201);
    }

    /**
     * Rate a supplier.
     */
    public function rateSupplier(Request $request, int $partyId): JsonResponse
    {
        $request->validate([
            'rating_quality' => 'required|integer|min:1|max:5',
            'rating_delivery' => 'required|integer|min:1|max:5',
            'rating_price' => 'required|integer|min:1|max:5',
            'rating_communication' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string',
            'is_public' => 'nullable|boolean',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
        ]);

        $supplier = Party::findOrFail($partyId);
        
        if ($supplier->business_id !== auth()->user()->business_id) {
            return response()->json([
                'message' => 'Unauthorized access to this supplier',
            ], 403);
        }

        $rating = $this->supplierPortalService->rateSupplier(
            $supplier,
            $request->all(),
            auth()->user()->business_id,
            auth()->id()
        );

        return response()->json([
            'message' => 'Supplier rating recorded successfully',
            'data' => $rating,
        ], 201);
    }

    /**
     * Get supplier analytics.
     */
    public function getAnalytics(Request $request, int $partyId): JsonResponse
    {
        $analytics = $this->supplierPortalService->getSupplierAnalytics(
            auth()->user()->business_id,
            $partyId,
            $request->all()
        );

        return response()->json([
            'data' => $analytics,
        ]);
    }
}