<?php

namespace Modules\ZSyst\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ZSyst\App\Models\SyncSession;

class SyncController
{
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string'],
            'status' => ['required', 'string', 'in:online,offline'],
            'last_sync_at' => ['nullable', 'date'],
            'payload' => ['nullable', 'array'],
        ]);

        $payload = $request->input('payload');

        $session = SyncSession::updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'status' => $data['status'],
                'last_sync_at' => $data['last_sync_at'] ?? now(),
                'payload' => is_array($payload) ? json_encode($payload) : null,
            ]
        );

        return response()->json([
            'message' => 'Sync heartbeat recorded',
            'data' => $session,
        ], 201);
    }

    public function status(string $deviceId): JsonResponse
    {
        $session = SyncSession::where('device_id', $deviceId)->firstOrFail();

        return response()->json($session);
    }
}
