<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageReceivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class MessagingService
{
    /**
     * Send a stock refill request.
     */
    public function sendStockRefillRequest(
        int $companyId,
        int $senderId,
        int $recipientId,
        int $branchId,
        array $products
    ): Message {
        return DB::transaction(function () use ($companyId, $senderId, $recipientId, $branchId, $products) {
            $message = Message::create([
                'company_id' => $companyId,
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'branch_id' => $branchId,
                'type' => Message::TYPE_STOCK_REFILL,
                'subject' => 'طلب تعبئة مخزون',
                'content' => 'تم طلب تعبئة المخزون للمنتجات التالية: ' . count($products) . ' منتج',
                'metadata' => [
                    'products' => $products,
                    'priority' => 'normal',
                ],
            ]);

            $this->sendNotification($recipientId, new MessageReceivedNotification($message));

            return $message;
        });
    }

    /**
     * Send prescription ready alert.
     */
    public function sendPrescriptionReadyAlert(
        int $companyId,
        int $prescriptionId,
        int $patientId,
        array $recipients
    ): Message {
        return DB::transaction(function () use ($companyId, $prescriptionId, $patientId, $recipients) {
            $message = Message::create([
                'company_id' => $companyId,
                'sender_id' => $recipients[0] ?? null,
                'recipient_id' => $recipients[0] ?? null,
                'type' => Message::TYPE_PRESCRIPTION_READY,
                'subject' => 'جاهز للصرف',
                'content' => 'الوصفة جاهزة للصرف',
                'metadata' => [
                    'prescription_id' => $prescriptionId,
                    'patient_id' => $patientId,
                ],
            ]);

            foreach ($recipients as $recipientId) {
                $this->sendNotification($recipientId, new MessageReceivedNotification($message));
            }

            return $message;
        });
    }

    /**
     * Send low stock alert.
     */
    public function sendLowStockAlert(
        int $companyId,
        int $productId,
        int $currentStock,
        int $alertQty
    ): ?Message {
        $managers = User::where('company_id', $companyId)
            ->whereHas('roles', fn($q) => $q->where('name', 'manager'))
            ->get();

        if ($managers->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($companyId, $productId, $currentStock, $alertQty, $managers) {
            $message = Message::create([
                'company_id' => $companyId,
                'sender_id' => $managers->first()->id,
                'recipient_id' => $managers->first()->id,
                'type' => Message::TYPE_LOW_STOCK,
                'subject' => 'تنبيه مخزون منخفض',
                'content' => 'المخزون منخفض للمنتج: ' . $productId,
                'metadata' => [
                    'product_id' => $productId,
                    'current_stock' => $currentStock,
                    'alert_qty' => $alertQty,
                ],
            ]);

            $managers->each(fn($manager) => 
                $this->sendNotification($manager->id, new MessageReceivedNotification($message))
            );

            return $message;
        });
    }

    /**
     * Get messages for a user.
     */
    public function getUserMessages(int $userId, int $companyId, array $filters = []): Collection
    {
        $query = Message::where('company_id', $companyId)
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                  ->orWhere('recipient_id', $userId);
            });

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (!empty($filters['unread'])) {
            $query->unread();
        }

        return $query->with(['sender:id,name', 'recipient:id,name'])
            ->latest()
            ->paginate($filters['per_page'] ?? 25)
            ->getCollection();
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(int $messageId, int $userId): bool
    {
        $message = Message::where('id', $messageId)
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                  ->orWhere('recipient_id', $userId);
            })
            ->first();

        if (!$message) {
            return false;
        }

        $message->markAsRead();
        return true;
    }

    /**
     * Reply to a message.
     */
    public function replyToMessage(
        int $parentId,
        int $senderId,
        string $content
    ): Message {
        $parentMessage = Message::findOrFail($parentId);

        return Message::create([
            'company_id' => $parentMessage->company_id,
            'sender_id' => $senderId,
            'recipient_id' => $parentMessage->sender_id,
            'type' => $parentMessage->type,
            'subject' => 'رد على: ' . $parentMessage->subject,
            'content' => $content,
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Send notification to user.
     */
    private function sendNotification(int $userId, MessageReceivedNotification $notification): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->notify($notification);
        }
    }

    /**
     * Get unread message count for a user.
     */
    public function getUnreadCount(int $userId, int $companyId): int
    {
        return Message::where('company_id', $companyId)
            ->where('recipient_id', $userId)
            ->unread()
            ->count();
    }
}