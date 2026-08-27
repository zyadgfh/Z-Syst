<?php

namespace App\Services;

use App\Models\TwoFactorAuth;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TwoFactorAuthService
{
    /**
     * Generate a new TOTP secret for a user.
     * Returns the secret (plaintext for QR code generation) and stores it encrypted.
     */
    public function generateSecret(User $user): array
    {
        $secret = $this->generateRandomBase32Secret();

        $twoFactor = TwoFactorAuth::updateOrCreate(
            ['user_id' => $user->id],
            [
                'secret' => $secret, // Will be encrypted via model mutator
            ]
        );

        return [
            'secret' => $secret,
            'otpauth_url' => $this->getOtpAuthUrl($user, $secret),
        ];
    }

    /**
     * Verify a TOTP code against the stored secret.
     */
    public function verifyCode(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorAuth;

        if (!$twoFactor || !$twoFactor->secret) {
            return false;
        }

        // Validate the code format (6 digits)
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        // Check against current and adjacent time windows (±1 step = ±30 seconds)
        $secret = $twoFactor->secret;
        $isValid = false;

        for ($offset = -1; $offset <= 1; $offset++) {
            if ($this->verifyTotpCode($secret, $code, $offset)) {
                $isValid = true;
                break;
            }
        }

        if ($isValid) {
            $twoFactor->recordVerification();
        }

        return $isValid;
    }

    /**
     * Verify a recovery code.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $twoFactor = $user->twoFactorAuth;

        if (!$twoFactor || !$twoFactor->recovery_codes) {
            return false;
        }

        $recoveryCodes = json_decode($twoFactor->recovery_codes, true) ?? [];

        // Find and remove the used recovery code
        $codeHash = hash('sha256', trim($code));
        $found = false;

        foreach ($recoveryCodes as $index => $hash) {
            if (hash_equals($hash, $codeHash)) {
                unset($recoveryCodes[$index]);
                $found = true;
                break;
            }
        }

        if ($found) {
            $twoFactor->update(['recovery_codes' => json_encode(array_values($recoveryCodes))]);
        }

        return $found;
    }

    /**
     * Generate recovery codes for a user.
     */
    public function generateRecoveryCodes(User $user): array
    {
        $codes = [];
        $hashedCodes = [];

        for ($i = 0; $i < 10; $i++) {
            $code = strtoupper(Str::random(4) . '-' . Str::random(4));
            $codes[] = $code;
            $hashedCodes[] = hash('sha256', $code);
        }

        $twoFactor = TwoFactorAuth::firstOrCreate(
            ['user_id' => $user->id],
            ['secret' => null]
        );

        $twoFactor->update(['recovery_codes' => json_encode($hashedCodes)]);

        return $codes; // Return plaintext codes (shown once to user)
    }

    /**
     * Enable 2FA for a user after verifying the initial code.
     */
    public function enable(User $user, string $code): bool
    {
        if ($this->verifyCode($user, $code)) {
            $user->twoFactorAuth->markEnabled();
            return true;
        }

        return false;
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable(User $user, string $password): bool
    {
        // Verify password before disabling
        if (!\Hash::check($password, $user->password)) {
            return false;
        }

        $twoFactor = $user->twoFactorAuth;
        if ($twoFactor) {
            $twoFactor->markDisabled();
            $twoFactor->update(['secret' => null, 'recovery_codes' => null]);
        }

        return true;
    }

    /**
     * Check if 2FA is enabled for a user.
     */
    public function isEnabled(User $user): bool
    {
        return $user->twoFactorAuth && $user->twoFactorAuth->isEnabled();
    }

    // ── Private Helpers ──

    /**
     * Generate a random Base32 secret (160 bits / 20 bytes).
     */
    private function generateRandomBase32Secret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * Build the otpauth:// URL for QR code generation.
     */
    private function getOtpAuthUrl(User $user, string $secret): string
    {
        $issuer = config('app.name', 'Z-Syst');
        $label = urlencode($user->email ?? $user->name);

        return "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
    }

    /**
     * Verify a TOTP code with a time offset.
     * Implements RFC 6238 TOTP algorithm.
     */
    private function verifyTotpCode(string $secret, string $code, int $offset = 0): bool
    {
        $time = floor((now()->timestamp + ($offset * 30)) / 30);

        // Decode Base32 secret
        $key = $this->base32Decode($secret);

        // Pack time as 8-byte big-endian
        $timeBytes = pack('N*', 0, (int) $time);

        // HMAC-SHA1
        $hmac = hash_hmac('sha1', $timeBytes, $key, true);

        // Dynamic truncation (RFC 4226)
        $offset = ord($hmac[strlen($hmac) - 1]) & 0x0F;
        $hashPart = substr($hmac, $offset, 4);

        $value = unpack('N', $hashPart)[1];
        $value = $value & 0x7FFFFFFF;

        $otp = $value % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT) === $code;
    }

    /**
     * Decode a Base32 string to binary.
     */
    private function base32Decode(string $input): string
    {
        $map = [
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5,
            'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11,
            'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17,
            'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
            'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29,
            '6' => 30, '7' => 31,
        ];

        $input = strtoupper(trim($input, '='));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $val = $map[$input[$i]] ?? -1;
            if ($val < 0) continue;

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
