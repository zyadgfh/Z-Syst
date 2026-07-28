<?php

namespace App\Services;

use Illuminate\Http\Request;

class TenantResolver
{
    public function resolve(Request $request): ?int
    {
        $user = $request->user();

        if ($user && isset($user->business_id)) {
            return (int) $user->business_id;
        }

        if ($request->has('business_id')) {
            return (int) $request->input('business_id');
        }

        return null;
    }
}
