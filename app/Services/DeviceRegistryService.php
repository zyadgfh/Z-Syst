<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DeviceRegistryService — identifies which enrolled client is talking to us.
 *
 * Web clients are fingerprinted from stable request attributes (business +
 * user agent + platform + language). POS/Flutter clients send their enrolled
 * X-Device-Token. Auto-registration and approval follow business settings.
 *
 * Honest limitation surfaced in the UI: a web server cannot smell USB
 * peripherals; scanners are assumed (keyboard-wedge), printers are either
 * declared here (enrolled/assigned) or discovered on the LAN by
 * NetworkDeviceDiscovery when the business opts in on a self-host.
 */
class DeviceRegistryService
{
    public function fingerprint(Request $request, int|string $businessId): string
    {
        $platform = $request->header('sec-ch-ua-platform', '');

        return hash('sha256', implode('|', [
            $businessId,
            (string) $request->userAgent(),
            (string) $platform,
            (string) $request->header('accept-language', ''),
        ]));
    }

    public function guessType(Request $request): string
    {
        $ua = (string) $request->userAgent();

        return match (true) {
            (bool) preg_match('/(tablet|ipad)/i', $ua) => Device::TYPE_TABLET,
            (bool) preg_match('/(mobile|iphone|android.*mobile)/i', $ua) => Device::TYPE_MOBILE,
            (bool) preg_match('/(android|cros)/i', $ua) => Device::TYPE_POS,
            default => Device::TYPE_PC,
        };
    }

    /** Resolve (and maybe auto-register) the device behind this request. */
    public function resolve(Request $request): ?Device
    {
        $user = $request->user();
        if (! $user?->business_id) {
            return null;
        }
        $businessId = (int) $user->business_id;

        // Enrolled client presenting its token wins.
        if ($token = (string) $request->header('X-Device-Token', $request->input('device_token', ''))) {
            $device = Device::forBusiness($businessId)->where('token', $token)->first();
            if ($device) {
                $this->heartbeat($device, $request);

                return $device;
            }
        }

        $fingerprint = $this->fingerprint($request, $businessId);
        $device = Device::forBusiness($businessId)->where('fingerprint', $fingerprint)->first();

        if (! $device) {
            if (! DeviceSettings::get($businessId, 'devices.auto_register', true)) {
                return null;
            }
            $device = Device::create([
                'business_id' => $businessId,
                'uuid' => (string) Str::ulid(),
                'fingerprint' => $fingerprint,
                'name' => $this->suggestName($request),
                'type' => $this->guessType($request),
                'platform' => (string) $request->header('sec-ch-ua-platform', ''),
                'user_agent' => (string) $request->userAgent(),
                'ip_address' => $request->ip(),
                'has_scanner' => true,
                'is_active' => true,
                'is_approved' => ! DeviceSettings::get($businessId, 'devices.require_approval', false),
                'last_seen_at' => now(),
            ]);
        } else {
            $this->heartbeat($device, $request);
        }

        return $device;
    }

    /** Explicit enrollment for POS/Flutter clients; token returned once. */
    public function enroll(Request $request, string $name, string $type, array $peripherals = []): Device
    {
        $businessId = (int) $request->user()->business_id;
        $fingerprint = $this->fingerprint($request, $businessId);

        $device = Device::forBusiness($businessId)->where('fingerprint', $fingerprint)->first()
            ?? new Device([
                'business_id' => $businessId,
                'uuid' => (string) Str::ulid(),
                'fingerprint' => $fingerprint,
                'is_active' => true,
                'is_approved' => ! DeviceSettings::get($businessId, 'devices.require_approval', false),
            ]);

        $device->fill([
            'name' => $name,
            'type' => in_array($type, [Device::TYPE_POS, Device::TYPE_TABLET, Device::TYPE_PC, Device::TYPE_MOBILE, Device::TYPE_OTHER], true)
                ? $type : Device::TYPE_OTHER,
            'platform' => (string) $request->header('sec-ch-ua-platform', ''),
            'user_agent' => (string) $request->userAgent(),
            'ip_address' => $request->ip(),
            'peripherals' => $peripherals,
            'has_scanner' => (bool) ($peripherals['scanner'] ?? true),
        ]);

        if (! $device->exists || ! $device->token) {
            $device->token = Str::random(60);
        }

        $device->last_seen_at = now();
        $device->save();

        return $device;
    }

    public function heartbeat(Device $device, ?Request $request = null): void
    {
        $device->forceFill([
            'last_seen_at' => now(),
            'ip_address' => $request?->ip() ?? $device->ip_address,
        ])->save();
    }

    private function suggestName(Request $request): string
    {
        $type = $this->guessType($request);

        return __(':type :n', ['type' => ucfirst($type), 'n' => Device::forBusiness((int) $request->user()->business_id)->count() + 1]);
    }
}
