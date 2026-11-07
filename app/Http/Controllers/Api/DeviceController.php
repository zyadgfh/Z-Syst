<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\DeviceRegistryService;
use App\Services\DeviceSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DeviceController — enrollment & self-description for POS/Flutter clients.
 * Any authenticated API user may enroll the device in their hand; management
 * (approval, printer assignment) stays in the admin settings hub.
 */
class DeviceController extends Controller
{
    public function __construct(protected DeviceRegistryService $registry)
    {
    }

    /** POST /devices/enroll — { name, type?, peripherals? } → uuid + token. */
    public function enroll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'type' => 'nullable|in:pos,tablet,pc,mobile,other',
            'peripherals' => 'nullable|array',
            'peripherals.scanner' => 'nullable|boolean',
            'peripherals.barcode_printer' => 'nullable|string|max:60',
            'peripherals.pos_printer' => 'nullable|string|max:60',
        ]);

        $device = $this->registry->enroll(
            $request,
            $validated['name'],
            $validated['type'] ?? Device::TYPE_POS,
            $validated['peripherals'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Device enrolled',
            'data' => [
                'uuid' => $device->uuid,
                'token' => $device->token, // shown once; client stores it
                'is_approved' => (bool) $device->is_approved,
            ],
        ], 201);
    }

    /** GET /devices/me — device + printers + scanner/print preferences. */
    public function me(Request $request): JsonResponse
    {
        $device = $this->registry->resolve($request);
        $businessId = (int) $request->user()->business_id;

        return response()->json([
            'success' => true,
            'data' => array_merge(
                $device?->clientPayload() ?? ['device' => null, 'printers' => ['barcode' => null, 'receipt' => null]],
                ['settings' => DeviceSettings::clientPayload($businessId)],
                ['approved' => (bool) ($device?->is_approved ?? true)]
            ),
        ]);
    }

    /** POST /devices/{uuid}/heartbeat. */
    public function heartbeat(Request $request, string $uuid): JsonResponse
    {
        $device = Device::forBusiness((int) $request->user()->business_id)
            ->where('uuid', $uuid)->firstOrFail();
        $this->registry->heartbeat($device, $request);

        return response()->json(['success' => true, 'data' => ['last_seen_at' => $device->last_seen_at->toIso8601String()]]);
    }

    /** GET /devices — the caller's business devices (POS multi-terminal views). */
    public function index(Request $request): JsonResponse
    {
        $devices = Device::forBusiness((int) $request->user()->business_id)
            ->with(['barcodePrinter:id,name', 'receiptPrinter:id,name'])
            ->orderByDesc('last_seen_at')->limit(100)->get();

        return response()->json([
            'success' => true,
            'data' => collect($devices)->map(fn (Device $device) => array_merge($device->clientPayload(), [
                'is_active' => $device->is_active,
                'ip_address' => $device->ip_address,
            ])),
        ]);
    }
}
