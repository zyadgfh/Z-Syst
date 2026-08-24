<?php

namespace App\Notifications;

use App\Models\InventoryAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InventoryCriticalAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public $alerts
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $alertCount = $this->alerts->count();
        $alertText = $alertCount === 1 ? 'تنبيه مخزون حرج واحد' : "{$alertCount} تنبيهات مخزون حرجة";

        $mail = (new MailMessage)
            ->subject("⚠ {$alertText}")
            ->greeting("مرحباً {$notifiable->name}")
            ->line("تم اكتشاف **{$alertText}** تتطلب اهتمامك الفوري:")
            ->line('---');

        foreach ($this->alerts->take(10) as $alert) {
            $severity = match ($alert->severity) {
                'critical' => '🔴 حرج',
                'warning'  => '🟡 تحذير',
                default    => 'ℹ️ معلومة',
            };

            $type = match ($alert->type) {
                'low_stock'    => 'مخزون منخفض',
                'out_of_stock' => 'نفذ من المخزون',
                'expiring_soon' => 'قرب انتهاء الصلاحية',
                'expired'      => 'منتهي الصلاحية',
                'overstock'    => 'مخزون زائد',
                default        => $alert->type,
            };

            $line = "{$severity} — **{$type}**: {$alert->message}";

            if ($alert->suggested_reorder_qty) {
                $line .= "\n  → الكمية المقترحة: {$alert->suggested_reorder_qty} وحدة";
            }

            $mail->line($line);
        }

        if ($alertCount > 10) {
            $mail->line("... و " . ($alertCount - 10) . " تنبيهات أخرى");
        }

        return $mail
            ->action('عرض التنبيهات', url('/admin/inventory-alerts'))
            ->line('تم إرسال هذا الإشعار تلقائياً بواسطة نظام المخزون.');
    }
}
