<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeviceRegistryService;
use App\Services\OfflineSyncService;
use Illuminate\Http\Request;

/** POST /api/v1/pos/sync — replay offline-queued sales (PWA / device). */
class OfflineSyncController extends Controller
{
    public function __construct(
        protected OfflineSyncService $sync,
        protected DeviceRegistryService $registry,
    ) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'sales' => 'required|array|max:100',
            'sales.*.client_uuid' => 'required|uuid',
            'sales.*.products' => 'required|array|min:1',
            'sales.*.products.*.product_id' => 'required|integer',
            'sales.*.products.*.price' => 'required|numeric',
            'sales.*.products.*.quantities' => 'required|integer|min:1',
        ]);

        try {
            $device = $this->registry->resolve($request);
        } catch (\Throwable $e) {
            $device = null;
        }

        $result = $this->sync->sync($data['sales'], $request->user(), $device);

        return response()->json(['success' => true, 'data' => $result]);
    }
}
