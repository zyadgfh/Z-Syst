<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Stock;
use App\Models\Prescription;
use App\Models\Party;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ExpiryAlertService;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificationService
{
    use WithTransactionalOperations;

    protected ExpiryAlertService $expiryAlertService;

    public function __construct(ExpiryAlertService $expiryAlertService)
    {
        $this->expiryAlertService = $expiryAlertService;
    }

    /**
     * Send low stock alert.
     *
     * @param int $businessId
     * @param array<int> $productIds
     * @param int $userId
     * @return Notification
     */
    public function sendLowStockAlert(int $businessId, array $productIds, int $userId): Notification
    {
        $lowStockItems = Stock::where('business_id', $businessId)
            ->whereIn('product_id', $productIds)
            ->where('productStock', '<=', 10)
            ->where('productStock', '>', 0)
            ->with('product:id,productName,alert_qty')
            ->get()
            ->filter(function ($stock) {
                $alertThreshold = $stock->product->alert_qty ?? 10;
                return $stock->productStock <= $alertThreshold;
            });

        if ($lowStockItems->isEmpty()) {
            return null;
        }

        return Notification::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'type' => 'low_stock_alert',
            'title' => 'Low Stock Alert',
            'message' => $this->formatLowStockMessage($lowStockItems),
            'data' => json_encode([
                'count' => $lowStockItems->count(),
                'items' => $lowStockItems->map(function ($stock) {
                    return [
                        'product_id' => $stock->product_id,
                        'product_name' => $stock->product->productName,
                        'current_stock' => $stock->productStock,
                        'alert_threshold' => $stock->product->alert_qty ?? 10,
                    ];
                }),
            ]),
            'read' => false,
        ]);
    }

    /**
     * Send expiry alert.
     *
     * @param int $businessId
     * @param array<int> $batchIds
     * @param int $userId
     * @return Notification
     */
    public function sendExpiryAlert(int $businessId, array $batchIds, int $userId): Notification
    {
        $batches = Stock::where('business_id', $businessId)
            ->whereIn('id', $batchIds)
            ->where('productStock', '>', 0)
            ->whereNotNull('expire_date')
            ->with('product:id,productName,purchase_without_tax')
            ->get();

        if ($batches->isEmpty()) {
            return null;
        }

        return Notification::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'type' => 'expiry_alert',
            'title' => 'Stock Expiry Alert',
            'message' => $this->formatExpiryMessage($batches),
            'data' => json_encode([
                'count' => $batches->count(),
                'batches' => $batches->map(function ($batch) {
                    return [
                        'batch_id' => $batch->id,
                        'product_id' => $batch->product_id,
                        'product_name' => $batch->product->productName,
                        'batch_no' => $batch->batch_no,
                        'expire_date' => $batch->expire_date,
                        'quantity' => $batch->productStock,
                        'value' => ($batch->product->purchase_without_tax ?? 0) * $batch->productStock,
                    ];
                }),
            ]),
            'read' => false,
        ]);
    }

    /**
     * Send prescription reminder.
     *
     * @param int $businessId
     * @param array<int> $prescriptionIds
     * @param int $userId
     * @return Notification
     */
    public function sendPrescriptionReminder(int $businessId, array $prescriptionIds, int $userId): Notification
    {
        $prescriptions = Prescription::where('business_id', $businessId)
            ->whereIn('id', $prescriptionIds)
            ->where('status', 'pending')
            ->where('review_status', 'approved')
            ->with(['party:id,name,phone', 'items.product'])
            ->get();

        if ($prescriptions->isEmpty()) {
            return null;
        }

        return Notification::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'type' => 'prescription_reminder',
            'title' => 'Prescription Reminder',
            'message' => $this->formatPrescriptionReminderMessage($prescriptions),
            'data' => json_encode([
                'count' => $prescriptions->count(),
                'prescriptions' => $prescriptions->map(function ($prescription) {
                    return [
                        'prescription_id' => $prescription->id,
                        'prescription_number' => $prescription->prescription_number,
                        'patient_name' => $prescription->patient_name,
                        'patient_phone' => $prescription->patient_phone,
                        'expires_at' => $prescription->expires_at,
                        'items_count' => $prescription->items->count(),
                    ];
                }),
            ]),
            'read' => false,
        ]);
    }

    /**
     * Send payment reminder.
     *
     * @param int $businessId
     * @param array<int> $invoiceIds
     * @param int $userId
     * @return Notification
     */
    public function sendPaymentReminder(int $businessId, array $invoiceIds, int $userId): Notification
    {
        $invoices = Invoice::where('business_id', $businessId)
            ->whereIn('id', $invoiceIds)
            ->where('balance', '>', 0)
            ->where('status', '!=', 'paid')
            ->with(['party:id,name,phone'])
            ->get();

        if ($invoices->isEmpty()) {
            return null;
        }

        return Notification::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'type' => 'payment_reminder',
            'title' => 'Payment Reminder',
            'message' => $this->formatPaymentReminderMessage($invoices),
            'data' => json_encode([
                'count' => $invoices->count(),
                'total_due' => $invoices->sum('balance'),
                'invoices' => $invoices->map(function ($invoice) {
                    return [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'customer_name' => $invoice->party->name,
                        'due_amount' => $invoice->balance,
                        'due_date' => $invoice->due_date,
                        'days_overdue' => $invoice->getDaysUntilDue(),
                    ];
                }),
            ]),
            'read' => false,
        ]);
    }

    /**
     * Send system notifications to multiple users.
     *
     * @param int $businessId
     * @param array<int> $userIds
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array<string, mixed> $data
     * @return Collection
     */
    public function sendBulkNotification(int $businessId, array $userIds, string $type, string $title, string $message, array $data = []): Collection
    {
        $notifications = collect();

        foreach ($userIds as $userId) {
            $notification = Notification::create([
                'business_id' => $businessId,
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => json_encode($data),
                'read' => false,
            ]);

            $notifications->push($notification);
        }

        return $notifications;
    }

    /**
     * Generate daily notifications summary.
     *
     * @param int $businessId
     * @return array
     */
    public function generateDailyNotificationsSummary(int $businessId): array
    {
        // Generate expiry alerts
        $expiryAlerts = $this->expiryAlertService->generateExpiryAlerts($businessId, [7, 30, 60]);
        
        // Generate low stock alerts
        $lowStockAlerts = $this->expiryAlertService->getLowStockAlerts($businessId);
        
        // Get pending prescriptions
        $pendingPrescriptions = Prescription::where('business_id', $businessId)
            ->where('status', 'pending')
            ->where('review_status', 'approved')
            ->whereDate('expires_at', '>=', now())
            ->count();

        // Get overdue invoices
        $overdueInvoices = Invoice::where('business_id', $businessId)
            ->where('balance', '>', 0)
            ->where('due_date', '<', now())
            ->count();

        return [
            'business_id' => $businessId,
            'date' => now()->toDateString(),
            'alerts' => [
                'expiry_alerts' => $expiryAlerts,
                'low_stock_alerts' => [
                    'count' => $lowStockAlerts->count(),
                    'items' => $lowStockAlerts,
                ],
                'pending_prescriptions' => $pendingPrescriptions,
                'overdue_invoices' => $overdueInvoices,
            ],
            'summary' => [
                'total_alerts' => count($expiryAlerts) + $lowStockAlerts->count() + $pendingPrescriptions + $overdueInvoices,
                'requires_attention' => count($expiryAlerts) > 0 || $lowStockAlerts->count() > 0 || $overdueInvoices > 0,
            ],
        ];
    }

    /**
     * Mark notification as read.
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $notification->update(['read' => true, 'read_at' => now()]);
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param int $businessId
     * @param int $userId
     * @return int
     */
    public function markAllAsRead(int $businessId, int $userId): int
    {
        return Notification::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('read', false)
            ->update(['read' => true, 'read_at' => now()]);
    }

    /**
     * Get unread notifications for a user.
     *
     * @param int $businessId
     * @param int $userId
     * @return Collection
     */
    public function getUnreadNotifications(int $businessId, int $userId): Collection
    {
        return Notification::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('read', false)
            ->latest()
            ->get();
    }

    /**
     * Get notification statistics.
     *
     * @param int $businessId
     * @param int $userId
     * @return array
     */
    public function getNotificationStatistics(int $businessId, int $userId): array
    {
        $total = Notification::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->count();

        $unread = Notification::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('read', false)
            ->count();

        $byType = Notification::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->groupBy('type')
            ->selectRaw('type, COUNT(*) as count')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $total - $unread,
            'by_type' => $byType,
        ];
    }

    /**
     * Format low stock message.
     *
     * @param Collection $lowStockItems
     * @return string
     */
    protected function formatLowStockMessage(Collection $lowStockItems): string
    {
        return sprintf(
            "%d products are running low on stock. Immediate replenishment recommended.",
            $lowStockItems->count()
        );
    }

    /**
     * Format expiry message.
     *
     * @param Collection $batches
     * @return string
     */
    protected function formatExpiryMessage(Collection $batches): string
    {
        $totalValue = $batches->sum(function ($batch) {
            return ($batch->product->purchase_without_tax ?? 0) * $batch->productStock;
        });

        return sprintf(
            "%d product batches are expiring soon. Total estimated value: %.2f",
            $batches->count(),
            $totalValue
        );
    }

    /**
     * Format prescription reminder message.
     *
     * @param Collection $prescriptions
     * @return string
     */
    protected function formatPrescriptionReminderMessage(Collection $prescriptions): string
    {
        return sprintf(
            "%d prescriptions are pending dispensing. Please review and process them.",
            $prescriptions->count()
        );
    }

    /**
     * Format payment reminder message.
     *
     * @param Collection $invoices
     * @return string
     */
    protected function formatPaymentReminderMessage(Collection $invoices): string
    {
        $totalDue = $invoices->sum('balance');

        return sprintf(
            "%d invoices have overdue payments. Total amount due: %.2f",
            $invoices->count(),
            $totalDue
        );
    }

    /**
     * Clean up old notifications.
     *
     * @param int $businessId
     * @param int $daysToKeep
     * @return int
     */
    public function cleanupOldNotifications(int $businessId, int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        return Notification::where('business_id', $businessId)
            ->where('read', true)
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Create notification for a specific event.
     *
     * @param int $businessId
     * @param int $userId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array<string, mixed> $data
     * @return Notification
     */
    public function createNotification(int $businessId, int $userId, string $type, string $title, string $message, array $data = []): Notification
    {
        return Notification::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => json_encode($data),
            'read' => false,
        ]);
    }
}