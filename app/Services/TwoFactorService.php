<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TwoFactorService
{
    private int $digits = 6;

    private int $period = 30;

    private string $issuer;

    public function __construct()
    {
        $this->issuer = config('app.name', 'Laravel');
    }

    public function generateSetup(User $user): array
    {
        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            return [
                'two_factor_enabled' => true,
                'message' => 'Two-factor authentication is already enabled.',
            ];
        }

        $secret = $user->two_factor_secret
            ? Crypt::decryptString($user->two_factor_secret)
            : $this->generateSecret();

        $user->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_confirmed_at' => null,
        ]);

        return [
            'secret' => $secret,
            'otpauth_url' => $this->getOtpAuthUrl($user, $secret),
            'two_factor_enabled' => false,
        ];
    }

    public function confirm(User $user, string $code): array
    {
        if (! $user->two_factor_secret) {
            throw ApiException::badRequest('Two-factor setup is not initialized.');
        }

        if (! $this->verify($user, $code)) {
            throw ApiException::unauthorized('Invalid two-factor authentication code.');
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
        ]);

        return [
            'two_factor_enabled' => true,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    public function disable(User $user, string $password, ?string $code = null): void
    {
        if (! $user->two_factor_confirmed_at) {
            throw ApiException::badRequest('Two-factor authentication is not enabled.');
        }

        if (! $user->validatePassword($password)) {
            throw ApiException::unauthorized('Your password is invalid.');
        }

        if ($code !== null && ! $this->verify($user, $code)) {
            throw ApiException::unauthorized('Invalid two-factor authentication code.');
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function verify(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $secret = Crypt::decryptString($user->two_factor_secret);

        if ($this->verifyTotp($secret, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    public function verifyTotp(string $secret, string $code): bool
    {
        $code = trim($code);

        foreach ([-1, 0, 1] as $window) {
            if (hash_equals($this->generateTotp($secret, $window), $code)) {
                return true;
            }
        }

        return false;
    }

    private function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(10));
    }

    private function getOtpAuthUrl(User $user, string $secret): string
    {
        $label = rawurlencode($this->issuer.':'.$user->email);
        $issuer = rawurlencode($this->issuer);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            $label,
            $secret,
            $issuer,
            $this->digits,
            $this->period
        );
    }

    private function generateTotp(string $secret, int $window = 0): string
    {
        $counter = floor(time() / $this->period) + $window;
        $secretKey = $this->base32Decode($secret);

        $binaryCounter = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $secretKey, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncatedHash = substr($hash, $offset, 4);
        $value = unpack('N', $truncatedHash)[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % (10 ** $this->digits)), $this->digits, '0', STR_PAD_LEFT);
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $codes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true);

        if (! is_array($codes)) {
            return false;
        }

        $normalized = array_map(fn ($item) => trim((string) $item), $codes);

        if (! in_array($code, $normalized, true)) {
            return false;
        }

        $remaining = array_values(array_filter($normalized, fn ($item) => ! hash_equals($item, $code)));

        $user->update([
            'two_factor_recovery_codes' => $remaining ? Crypt::encryptString(json_encode($remaining)) : null,
        ]);

        return true;
    }

    private function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(10));
        }

        return $codes;
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';

        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $fiveBit = str_split(str_pad($binary, ceil(strlen($binary) / 5) * 5, '0', STR_PAD_RIGHT), 5);

        return implode('', array_map(fn ($bits) => $alphabet[bindec($bits)], $fiveBit));
    }

    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = str_replace('=', '', strtoupper($secret));
        $binary = '';

        foreach (str_split($secret) as $char) {
            $index = strpos($alphabet, $char);

            if ($index === false) {
                continue;
            }

            $binary .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $bytes = str_split($binary, 8);
        $decoded = '';

        foreach ($bytes as $byte) {
            if (strlen($byte) === 8) {
                $decoded .= chr(bindec($byte));
            }
        }

        return $decoded;
    }
}
