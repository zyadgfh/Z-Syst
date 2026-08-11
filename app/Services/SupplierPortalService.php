<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Party;
use App\Models\SupplierPortalUser;
use App\Models\SupplierOrderResponse;
use App\Models\SupplierShipment;
use App\Models\SupplierPortalInvoice;
use App\Models\SupplierRating;
use App\Models\SupplierPortalActivity;
use App\Models\SupplierNotification;
use App\Models\PurchaseOrder;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SupplierPortalService
{
    use WithTransactionalOperations;

    /**
     * Create a supplier portal user.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return SupplierPortalUser
     * @throws \Exception
     */
    public function createSupplierUser(array $data, int $businessId): SupplierPortalUser
    {
        return $this->executeTransaction(function () use ($data, $businessId) {
            $user = SupplierPortalUser::create([
                'business_id' => $businessId,
                'party_id' => $data['party_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'] ?? 'viewer',
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Log activity
            $this->logActivity($businessId, $user->id, 'user_created', 'SupplierPortalUser', $user->id, [
                'created_by' => auth()->id(),
            ]);

            // Send welcome notification
            $this->sendNotification($businessId, $user->id, 'account_created', 'Account Created', 
                'Your supplier portal account has been created successfully.');

            return $user->fresh();
        });
    }

    /**
     * Respond to a purchase order.
     *
     * @param PurchaseOrder $purchaseOrder
     * @param array<string, mixed> $responseData
     * @param int $supplierUserId
     * @return SupplierOrderResponse
     * @throws \Exception
     */
    public function respondToOrder(PurchaseOrder $purchaseOrder, array $responseData, int $supplierUserId): SupplierOrderResponse
    {
        return $this->executeTransaction(function () use ($purchaseOrder, $responseData, $supplierUserId) {
            $response = SupplierOrderResponse::create([
                'business_id' => $purchaseOrder->business_id,
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_portal_user_id' => $supplierUserId,
                'response_type' => $responseData['response_type'],
                'response_notes' => $responseData['response_notes'] ?? null,
                'proposed_total' => $responseData['proposed_total'] ?? null,
                'proposed_delivery_date' => $responseData['proposed_delivery_date'] ?? null,
                'modifications' => $responseData['modifications'] ?? null,
                'responded_at' => now(),
            ]);

            // Update purchase order status
            $purchaseOrder->update([
                'status' => $responseData['response_type'] === 'accepted' ? 'confirmed' : 'pending_review',
            ]);

            // Log activity
            $this->logActivity($purchaseOrder->business_id, $supplierUserId, 'order_responded', 
                'PurchaseOrder', $purchaseOrder->id, [
                    'response_type' => $responseData['response_type'],
                ]);

            // Notify business users
            $this->notifyBusinessUsers($purchaseOrder->business_id, 'order_response_received', 
                'Order Response Received', 
                "Supplier has responded to purchase order #{$purchaseOrder->order_number}");

            return $response->fresh(['purchaseOrder', 'supplierUser']);
        });
    }

    /**
     * Create a shipment.
     *
     * @param PurchaseOrder $purchaseOrder
     * @param array<string, mixed> $shipmentData
     * @param int $supplierUserId
     * @return SupplierShipment
     * @throws \Exception
     */
    public function createShipment(PurchaseOrder $purchaseOrder, array $shipmentData, int $supplierUserId): SupplierShipment
    {
        return $this->executeTransaction(function () use ($purchaseOrder, $shipmentData, $supplierUserId) {
            $shipment = SupplierShipment::create([
                'business_id' => $purchaseOrder->business_id,
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_portal_user_id' => $supplierUserId,
                'tracking_number' => $shipmentData['tracking_number'] ?? null,
                'carrier' => $shipmentData['carrier'] ?? null,
                'status' => 'shipped',
                'estimated_delivery_date' => $shipmentData['estimated_delivery_date'] ?? null,
                'shipping_notes' => $shipmentData['shipping_notes'] ?? null,
                'delivery_address' => $shipmentData['delivery_address'] ?? null,
                'delivery_contact' => $shipmentData['delivery_contact'] ?? null,
                'delivery_phone' => $shipmentData['delivery_phone'] ?? null,
            ]);

            // Update purchase order status
            $purchaseOrder->update(['status' => 'shipped']);

            // Log activity
            $this->logActivity($purchaseOrder->business_id, $supplierUserId, 'shipment_created', 
                'SupplierShipment', $shipment->id, [
                    'tracking_number' => $shipment->tracking_number,
                ]);

            // Notify business users
            $this->notifyBusinessUsers($purchaseOrder->business_id, 'shipment_created', 
                'Shipment Created', 
                "Shipment created for order #{$purchaseOrder->order_number}. Tracking: {$shipment->tracking_number}");

            return $shipment->fresh(['purchaseOrder', 'supplierUser']);
        });
    }

    /**
     * Upload supplier invoice.
     *
     * @param PurchaseOrder $purchaseOrder
     * @param array<string, mixed> $invoiceData
     * @param int $supplierUserId
     * @return SupplierPortalInvoice
     * @throws \Exception
     */
    public function uploadInvoice(PurchaseOrder $purchaseOrder, array $invoiceData, int $supplierUserId): SupplierPortalInvoice
    {
        return $this->executeTransaction(function () use ($purchaseOrder, $invoiceData, $supplierUserId) {
            $file = $invoiceData['file'];
            $path = $file->store('supplier-invoices', 'public');

            $invoice = SupplierPortalInvoice::create([
                'business_id' => $purchaseOrder->business_id,
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_portal_user_id' => $supplierUserId,
                'invoice_number' => $invoiceData['invoice_number'],
                'invoice_date' => $invoiceData['invoice_date'],
                'amount' => $invoiceData['amount'],
                'currency' => $invoiceData['currency'] ?? 'USD',
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_mime_type' => $file->getMimeType(),
                'status' => 'pending',
            ]);

            // Log activity
            $this->logActivity($purchaseOrder->business_id, $supplierUserId, 'invoice_uploaded', 
                'SupplierPortalInvoice', $invoice->id, [
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->amount,
                ]);

            // Notify business users
            $this->notifyBusinessUsers($purchaseOrder->business_id, 'invoice_uploaded', 
                'Invoice Uploaded', 
                "Supplier uploaded invoice #{$invoice->invoice_number} for order #{$purchaseOrder->order_number}");

            return $invoice->fresh(['purchaseOrder', 'supplierUser']);
        });
    }

    /**
     * Rate a supplier.
     *
     * @param Party $supplier
     * @param array<string, mixed> $ratingData
     * @param int $businessId
     * @param int $userId
     * @return SupplierRating
     * @throws \Exception
     */
    public function rateSupplier(Party $supplier, array $ratingData, int $businessId, int $userId): SupplierRating
    {
        return $this->executeTransaction(function () use ($supplier, $ratingData, $businessId, $userId) {
            $overallRating = (
                ($ratingData['rating_quality'] ?? 0) +
                ($ratingData['rating_delivery'] ?? 0) +
                ($ratingData['rating_price'] ?? 0) +
                ($ratingData['rating_communication'] ?? 0)
            ) / 4;

            $rating = SupplierRating::create([
                'business_id' => $businessId,
                'party_id' => $supplier->id,
                'purchase_order_id' => $ratingData['purchase_order_id'] ?? null,
                'rating_quality' => $ratingData['rating_quality'] ?? 0,
                'rating_delivery' => $ratingData['rating_delivery'] ?? 0,
                'rating_price' => $ratingData['rating_price'] ?? 0,
                'rating_communication' => $ratingData['rating_communication'] ?? 0,
                'overall_rating' => $overallRating,
                'review' => $ratingData['review'] ?? null,
                'is_public' => $ratingData['is_public'] ?? true,
                'created_by' => $userId,
            ]);

            return $rating->fresh(['party', 'purchaseOrder', 'creator']);
        });
    }

    /**
     * Get supplier dashboard data.
     *
     * @param int $businessId
     * @param int $partyId
     * @return array
     */
    public function getSupplierDashboard(int $businessId, int $partyId): array
    {
        $pendingOrders = PurchaseOrder::where('business_id', $businessId)
            ->where('party_id', $partyId)
            ->whereIn('status', ['pending', 'pending_review'])
            ->count();

        $confirmedOrders = PurchaseOrder::where('business_id', $businessId)
            ->where('party_id', $partyId)
            ->where('status', 'confirmed')
            ->count();

        $shippedOrders = PurchaseOrder::where('business_id', $businessId)
            ->where('party_id', $partyId)
            ->where('status', 'shipped')
            ->count();

        $pendingInvoices = SupplierPortalInvoice::where('business_id', $businessId)
            ->whereHas('purchaseOrder', function ($q) use ($partyId) {
                $q->where('party_id', $partyId);
            })
            ->where('status', 'pending')
            ->count();

        $totalRevenue = SupplierPortalInvoice::where('business_id', $businessId)
            ->whereHas('purchaseOrder', function ($q) use ($partyId) {
                $q->where('party_id', $partyId);
            })
            ->where('status', 'paid')
            ->sum('amount');

        $averageRating = SupplierRating::where('business_id', $businessId)
            ->where('party_id', $partyId)
            ->avg('overall_rating') ?? 0;

        return [
            'summary' => [
                'pending_orders' => $pendingOrders,
                'confirmed_orders' => $confirmedOrders,
                'shipped_orders' => $shippedOrders,
                'pending_invoices' => $pendingInvoices,
                'total_revenue' => $totalRevenue,
                'average_rating' => round($averageRating, 1),
            ],
            'recent_orders' => PurchaseOrder::where('business_id', $businessId)
                ->where('party_id', $partyId)
                ->latest()
                ->take(10)
                ->get(),
            'recent_activities' => SupplierPortalActivity::where('business_id', $businessId)
                ->whereHas('supplierUser', function ($q) use ($partyId) {
                    $q->where('party_id', $partyId);
                })
                ->latest()
                ->take(10)
                ->get(),
        ];
    }

    /**
     * Get pending orders for supplier.
     *
     * @param int $businessId
     * @param int $partyId
     * @return Collection
     */
    public function getPendingOrders(int $businessId, int $partyId): Collection
    {
        return PurchaseOrder::where('business_id', $businessId)
            ->where('party_id', $partyId)
            ->whereIn('status', ['pending', 'pending_review'])
            ->with(['items.product', 'business'])
            ->latest()
            ->get();
    }

    /**
     * Log supplier portal activity.
     *
     * @param int $businessId
     * @param int|null $supplierUserId
     * @param string $action
     * @param string|null $entityType
     * @param int|null $entityId
     * @param array<string, mixed> $details
     * @return void
     */
    protected function logActivity(int $businessId, ?int $supplierUserId, string $action, ?string $entityType, ?int $entityId, array $details = []): void
    {
        SupplierPortalActivity::create([
            'business_id' => $businessId,
            'supplier_portal_user_id' => $supplierUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Send notification to supplier user.
     *
     * @param int $businessId
     * @param int $supplierUserId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array<string, mixed> $data
     * @return void
     */
    protected function sendNotification(int $businessId, int $supplierUserId, string $type, string $title, string $message, array $data = []): void
    {
        SupplierNotification::create([
            'business_id' => $businessId,
            'supplier_portal_user_id' => $supplierUserId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Notify business users about supplier actions.
     *
     * @param int $businessId
     * @param string $type
     * @param string $title
     * @param string $message
     * @return void
     */
    protected function notifyBusinessUsers(int $businessId, string $type, string $title, string $message): void
    {
        // This would integrate with the existing NotificationService
        // For now, we'll create a placeholder
        // NotificationService would be called here to notify business users
    }

    /**
     * Get supplier analytics.
     *
     * @param int $businessId
     * @param int $partyId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getSupplierAnalytics(int $businessId, int $partyId, array $filters = []): array
    {
        $query = PurchaseOrder::where('business_id', $businessId)
            ->where('party_id', $partyId);

        // Apply date filters
        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $orders = $query->get();

        return [
            'total_orders' => $orders->count(),
            'total_value' => $orders->sum('total_amount'),
            'average_order_value' => $orders->count() > 0 ? $orders->sum('total_amount') / $orders->count() : 0,
            'by_status' => $orders->groupBy('status')->map->count(),
            'monthly_trend' => $orders->groupBy(function ($order) {
                return $order->created_at->format('Y-m');
            })->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('total_amount'),
                ];
            }),
        ];
    }
}