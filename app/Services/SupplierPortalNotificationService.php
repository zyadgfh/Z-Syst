<?php

namespace App\Services;

use App\Models\SupplierNotification;
use App\Models\SupplierPortalUser;

/** Internal portal notifications only; never sends email or SMS. */
class SupplierPortalNotificationService
{
    public function notify(int $businessId, ?int $supplierId, string $type, string $title, string $message, array $data = []): void
    {
        if (! $supplierId) {
            return;
        }

        SupplierPortalUser::where('business_id', $businessId)
            ->where('party_id', $supplierId)
            ->where('is_active', true)
            ->select('id')
            ->each(function (SupplierPortalUser $user) use ($businessId, $type, $title, $message, $data) {
                SupplierNotification::create([
                    'business_id' => $businessId,
                    'supplier_portal_user_id' => $user->id,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                ]);
            });
    }
}
