<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All available notification types with Arabic labels.
     */
    public static function availableTypes(): array
    {
        return [
            'inventory_alert'   => ['label' => 'تنبيهات المخزون', 'description' => 'تنبيهات المخزون المنخفض والمنتهي والقريب الانتهاء'],
            'loyalty_expiry'    => ['label' => 'انتهاء نقاط الولاء', 'description' => 'تنبيه انتهاء صلاحية نقاط الولاء قبل 30 يوماً'],
            'order_update'      => ['label' => 'تحديثات الطلبات', 'description' => 'إشعارات عند تحديث حالة الطلبات'],
            'new_review'        => ['label' => 'تقييمات جديدة', 'description' => 'إشعارات عند وجود تقييمات جديدة للمنتجات'],
            'low_stock'         => ['label' => 'مخزون منخفض', 'description' => 'تنبيهات خاصة بالمنتجات ذات المخزون المنخفض'],
            'security_alert'    => ['label' => 'تنبيهات أمنية', 'description' => 'تنبيهات أمنية مهمة ومحاولات تسجيل دخول مشبوهة'],
        ];
    }

    /**
     * Check if a notification type is enabled for a user.
     * Defaults to true if no preference is set.
     */
    public static function isEnabled(int $userId, string $type): bool
    {
        $pref = static::where('user_id', $userId)
            ->where('notification_type', $type)
            ->first();

        return $pref ? $pref->is_enabled : true;
    }

    /**
     * Toggle a notification type for a user.
     */
    public static function toggle(int $userId, string $type): bool
    {
        $pref = static::updateOrCreate(
            ['user_id' => $userId, 'notification_type' => $type],
            ['is_enabled' => true] // Will be flipped below
        );

        $pref->update(['is_enabled' => !$pref->is_enabled]);
        return $pref->is_enabled;
    }

    /**
     * Set all notification types for a user.
     */
    public static function setAll(int $userId, array $types): void
    {
        foreach ($types as $type => $enabled) {
            static::updateOrCreate(
                ['user_id' => $userId, 'notification_type' => $type],
                ['is_enabled' => $enabled]
            );
        }
    }
}
