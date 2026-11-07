<?php

namespace App\Services;

/**
 * NetworkDeviceDiscovery — LAN probing for printers/hosts (switches, POS
 * print boxes) on common service ports.
 *
 * Capability contract (surfaced honestly in the UI):
 *  - Requires the business to opt in (devices.network_scan) AND a self-host
 *    runtime with stream sockets (php-wasm/sandbox: unavailable → the UI
 *    reports that and lists enrolled devices instead).
 *  - Probes are connect-only with short timeouts (passive inventory, no
 *    exploit attempts).
 */
class NetworkDeviceDiscovery
{
    private const PROBES = [
        ['port' => 9100, 'label' => 'JetDirect/RAW print'],
        ['port' => 631, 'label' => 'IPP/print'],
        ['port' => 515, 'label' => 'LPD print'],
        ['port' => 80, 'label' => 'web admin'],
        ['port' => 443, 'label' => 'web admin'],
        ['port' => 22, 'label' => 'managed switch/server'],
    ];

    /** @return array{available: bool, reason?: string, hosts?: array} */
    public function discover(string $cidr = 'auto'): array
    {
        if (! function_exists('fsockopen')) {
            return ['available' => false, 'reason' => 'sockets unavailable in this runtime'];
        }

        $local = $this->localIp();
        if (! $local) {
            return ['available' => false, 'reason' => 'no local IP — run on a self-host inside the shop LAN'];
        }

        $net = $cidr === 'auto' ? implode('.', array_slice(explode('.', $local), 0, 3)).'.' : rtrim($cidr, '.').'.';
        $hosts = [];

        foreach (range(1, 254) as $n) {
            $ip = $net.$n;
            foreach (self::PROBES as [$port, $label]) {
                if ($this->portOpen($ip, $port)) {
                    $hosts[] = [
                        'ip' => $ip,
                        'port' => $port,
                        'service' => $label,
                        'guessed_kind' => in_array($port, [9100, 631, 515], true) ? 'printer' : ($port === 22 ? 'switch/server' : 'host'),
                    ];
                }
            }
        }

        return ['available' => true, 'hosts' => $hosts];
    }

    /** Single-host probe used by the "test" button on a network printer. */
    public function probeHost(string $host): bool
    {
        [$ip, $port] = array_pad(explode(':', $host, 2), 2, '9100');

        return $this->portOpen($ip, (int) $port);
    }

    private function portOpen(string $ip, int $port, int $timeoutMs = 220): bool
    {
        $sock = @fsockopen($ip, $port, $errno, $errstr, $timeoutMs / 1000);
        if ($sock) {
            fclose($sock);

            return true;
        }

        return false;
    }

    private function localIp(): ?string
    {
        // UDP connect trick: no packets are sent, but the routing table
        // picks the outbound interface address.
        $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($sock) {
            @socket_connect($sock, '8.8.8.8', 53);
            @socket_getsockname($sock, $addr);
            socket_close($sock);
            if (! str_starts_with((string) $addr, '127.')) {
                return $addr;
            }
        }

        return $_SERVER['SERVER_ADDR'] ?? null;
    }
}
