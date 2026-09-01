<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Notification $notification): bool
    {
        // Users can only view their own notifications
        return $notification->notifiable_id === $user->id;
    }

    public function create(User $user): bool
    {
        // Notifications are typically created by the system, not users
        return in_array($user->role, ['admin', 'superadmin']);
    }

    public function update(User $user, Notification $notification): bool
    {
        // Users can mark their own notifications as read
        return $notification->notifiable_id === $user->id;
    }

    public function delete(User $user, Notification $notification): bool
    {
        // Users can delete their own notifications
        return $notification->notifiable_id === $user->id;
    }

    public function markAsRead(User $user, Notification $notification): bool
    {
        return $notification->notifiable_id === $user->id;
    }

    public function markAsUnread(User $user, Notification $notification): bool
    {
        return $notification->notifiable_id === $user->id;
    }
}
