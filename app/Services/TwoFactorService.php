<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();
        $recoveryCodes = $this->generateRecoveryCodes();

        // Store temporarily (not enabled yet)
        Cache::put("2fa_setup:{$user->id}", [
            'secret' => $secret,
            'recovery_codes' => $recoveryCodes,
        ], now()->addMinutes(15));

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    public function enable(User $user, string $code): bool
    {
        $setupData = Cache::get("2fa_setup:{$user->id}");

        if (! $setupData) {
            return false;
        }

        if (! $this->verifyCode($setupData['secret'], $code)) {
            return false;
        }

        // Enable 2FA
        $user->update([
            'two_factor_secret' => encrypt($setupData['secret']),
            'two_factor_recovery_codes' => encrypt(json_encode($setupData['recovery_codes'])),
            'two_factor_confirmed_at' => now(),
        ]);

        // Clear temporary data
        Cache::forget("2fa_setup:{$user->id}");

        return true;
    }

    public function disable(User $user, string $code): bool
    {
        if (! $this->verify($user, $code)) {
            return false;
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return true;
    }

    public function verify(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        // Check if it's a recovery code
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        if (in_array($code, $recoveryCodes)) {
            // Remove used recovery code
            $recoveryCodes = array_diff($recoveryCodes, [$code]);
            $user->update([
                'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes))),
            ]);
            return true;
        }

        // Verify TOTP code
        return $this->verifyCode($secret, $code);
    }

    public function regenerateRecoveryCodes(User $user, string $code): array
    {
        if (! $this->verify($user, $code)) {
            throw new \Exception('Invalid verification code');
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return $recoveryCodes;
    }

    protected function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    protected function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))->map(function () {
            return Str::random(10) . '-' . Str::random(10);
        })->toArray();
    }
}
