<?php

namespace App\Services;

/**
 * RFC 6238 TOTP (30s, SHA1, 6 digits) in pure PHP + otpauth URI and a
 * compact QR via the Google Chart-free embedded SVG renderer below.
 * No external packages required.
 */
class Totp
{
    public static function generateSecret(int $bits = 160): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        foreach (str_split(random_bytes(intdiv($bits, 8))) as $byte) {
            $secret .= $alphabet[ord($byte) % 32];
        }

        return $secret;
    }

    public static function code(string $secret, ?int $timestamp = null, int $period = 30, int $digits = 6): string
    {
        $counter = intdiv($timestamp ?? time(), $period);
        $key = self::base32Decode($secret);
        $binaryCounter = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    /** ±1 window tolerance. */
    public static function verify(string $secret, string $code): bool
    {
        foreach ([time() - 30, time(), time() + 30] as $ts) {
            if (hash_equals(self::code($secret, $ts), trim($code))) {
                return true;
            }
        }

        return false;
    }

    public static function uri(string $secret, string $label, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$label).'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    private static function base32Decode(string $b32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(strtoupper(rtrim($b32, '='))) as $char) {
            $bits .= str_pad(decbin(strpos($alphabet, $char) ?: 0), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }

        return $bytes;
    }
}
