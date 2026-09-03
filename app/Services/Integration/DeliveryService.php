<?php

namespace App\Services\Integration;

use App\Models\SupplierShipment;
use App\Models\Delivery;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;

class DeliveryService
{
    use WithTransactionalOperations;

    /**
     * Create a delivery record.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return Delivery
     */
    public function createDelivery(array $data, int $businessId): Delivery
    {
        return Delivery::create([
            'business_id' => $businessId,
            'purchase_order_id' => $data['purchase_order_id'] ?? null,
            'supplier_shipment_id' => $data['supplier_shipment_id'] ?? null,
            'tracking_number' => $data['tracking_number'],
            'carrier' => $data['carrier'],
            'status' => 'pending',
            'estimated_delivery_date' => $data['estimated_delivery_date'] ?? null,
            'delivery_address' => $data['delivery_address'],
            'delivery_contact' => $data['delivery_contact'],
            'delivery_phone' => $data['delivery_phone'],
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Track delivery status from carrier API.
     *
     * @param string $trackingNumber
     * @param string $carrier
     * @return array
     */
    public function trackDelivery(string $trackingNumber, string $carrier): array
    {
        // Integrate with carrier API (FedEx, UPS, DHL, local couriers)
        // For now, simulate the response
        
        return [
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
            'status' => 'in_transit',
            'estimated_delivery' => now()->addDays(3)->toDateString(),
            'tracking_events' => [
                [
                    'date' => now()->subDay()->toDateString(),
                    'status' => 'picked_up',
                    'location' => 'Origin Warehouse',
                ],
                [
                    'date' => now()->toDateString(),
                    'status' => 'in_transit',
                    'location' => 'Distribution Center',
                ],
            ],
        ];
    }

    /**
     * Update delivery status.
     *
     * @param Delivery $delivery
     * @param string $status
     * @param array<string, mixed> $metadata
     * @return bool
     */
    public function updateDeliveryStatus(Delivery $delivery, string $status, array $metadata = []): bool
    {
        return $delivery->update([
            'status' => $status,
            'actual_delivery_date' => $status === 'delivered' ? now() : null,
            'metadata' => array_merge($delivery->metadata ?? [], $metadata),
        ]);
    }

    /**
     * Get delivery analytics.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getDeliveryAnalytics(int $businessId, array $filters = []): array
    {
        $query = Delivery::where('business_id', $businessId);

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $deliveries = $query->get();

        return [
            'total_deliveries' => $deliveries->count(),
            'pending' => $deliveries->where('status', 'pending')->count(),
            'in_transit' => $deliveries->where('status', 'in_transit')->count(),
            'delivered' => $deliveries->where('status', 'delivered')->count(),
            'failed' => $deliveries->where('status', 'failed')->count(),
            'by_carrier' => $deliveries->groupBy('carrier')->map->count(),
            'average_delivery_time' => $this->calculateAverageDeliveryTime($deliveries),
        ];
    }

    /**
     * Calculate average delivery time.
     *
     * @param Collection $deliveries
     * @return float
     */
    protected function calculateAverageDeliveryTime(Collection $deliveries): float
    {
        $delivered = $deliveries->where('status', 'delivered');

        if ($delivered->isEmpty()) {
            return 0;
        }

        $totalDays = $delivered->sum(function ($delivery) {
            return $delivery->actual_delivery_date 
                ? $delivery->actual_delivery_date->diffInDays($delivery->created_at)
                : 0;
        });

        return $totalDays / $delivered->count();
    }
}