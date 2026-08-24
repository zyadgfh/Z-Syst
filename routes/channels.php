<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Item stock updates — only users from the same business can listen
Broadcast::channel('item.{productId}.stock', function ($user, $productId) {
    $product = \App\Models\Product::find($productId);
    if ($product && $product->business_id === $user->business_id) {
        return true;
    }
    return false;
});

// Inventory alerts — only admins from the same business
Broadcast::channel('inventory-alerts.{businessId}', function ($user, $businessId) {
    return in_array($user->role, ['admin', 'superadmin']) &&
           (int) $user->business_id === (int) $businessId;
});
