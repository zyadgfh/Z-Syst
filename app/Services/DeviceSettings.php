<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;

/**
 * DeviceSettings — typed, per-business preferences for devices, scanners,
 * printing prompts and the POS. Stored in business_settings (JSON) under
 * group keys ("devices.*", "scanner.*", "print.*"); defaults below are the
 * single source of truth for shape.
 *
 * Defaults honor the owner's workflow: scanning auto-adds to the sale,
 * finishing a PURCHASE asks to print barcodes, finishing a SALE does not.
 */
class DeviceSettings
{
    public static function defaults(): array
    {
        return [
            'devices' => [
                'auto_register' => true,     // enroll unknown client fingerprints automatically
                'require_approval' => false, // block unapproved devices from POS features
                'network_scan' => false,     // opt-in LAN printer discovery (self-host only)
            ],
            'scanner' => [
                'auto_add' => true,          // scan → instantly add line to the sale
                'auto_focus' => true,        // keep the POS search box focused
                'beep' => true,              // audio feedback on add
                'min_chars' => 4,
                'max_input_ms' => 150,       // keyboard-wedge detection window
            ],
            'print' => [
                'barcode_on_purchase_complete' => true,  // GRN accept → propose barcode labels
                'barcode_on_sale' => false,              // sale → no barcode prompt (per owner)
                'receipt_on_sale' => 'ask',              // auto|ask|never
                'default_symbology' => 'EAN13',
                'default_size' => 'standard',
                'max_labels_per_item' => 10,             // cap for auto-mint on purchase
            ],
        ];
    }

    /** Full merged settings for a business. */
    public static function all(int $businessId): array
    {
        return Cache::remember(
            "device-settings:{$businessId}",
            600,
            fn () => self::merge($businessId)
        );
    }

    /** One group ("devices"|"scanner"|"print") merged over defaults. */
    public static function group(int $businessId, string $group): array
    {
        return self::all($businessId)[$group] ?? [];
    }

    public static function get(int $businessId, string $dottedKey, mixed $default = null): mixed
    {
        $groups = self::all($businessId);
        [$group, $key] = array_pad(explode('.', $dottedKey, 2), 2, null);

        return $groups[$group][$key] ?? $default;
    }

    /** Persist dot-keyed overrides: ['print.barcode_on_sale' => true, …]. */
    public static function update(int $businessId, array $overrides): void
    {
        foreach ($overrides as $dotted => $value) {
            [$group, $key] = array_pad(explode('.', $dotted, 2), 2, null);
            if (! $group || ! $key || ! isset(self::defaults()[$group]) || ! array_key_exists($key, self::defaults()[$group])) {
                continue; // refuse unknown keys — the defaults array is the schema
            }
            BusinessSetting::updateOrCreate(
                ['business_id' => $businessId, 'key' => $dotted],
                ['value' => $value]
            );
        }
        Cache::forget("device-settings:{$businessId}");
    }

    /** The subset a POS client should receive. */
    public static function clientPayload(int $businessId): array
    {
        $all = self::all($businessId);

        return [
            'scanner' => $all['scanner'],
            'print' => [
                'barcode_on_sale' => $all['print']['barcode_on_sale'],
                'receipt_on_sale' => $all['print']['receipt_on_sale'],
                'default_symbology' => $all['print']['default_symbology'],
                'default_size' => $all['print']['default_size'],
            ],
        ];
    }

    private static function merge(int $businessId): array
    {
        $merged = self::defaults();
        BusinessSetting::forBusiness($businessId)
            ->get()
            ->each(function (BusinessSetting $row) use (&$merged) {
                [$group, $key] = array_pad(explode('.', $row->key, 2), 2, null);
                if ($key && isset($merged[$group]) && array_key_exists($key, $merged[$group])) {
                    $merged[$group][$key] = $row->value;
                }
            });

        return $merged;
    }
}
