<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

/**
 * Trait for automatic encryption/decryption of sensitive model attributes.
 *
 * Usage:
 *   use EncryptableAttribute;
 *
 *   protected $encryptable = ['phone', 'email', 'ssn'];
 *
 * The trait overrides getAttribute and setAttribute to transparently
 * encrypt on save and decrypt on read.
 */
trait EncryptableAttribute
{
    /**
     * Get the names of attributes that should be encrypted.
     */
    public function getEncryptable(): array
    {
        return $this->encryptable ?? [];
    }

    /**
     * Override getAttribute to decrypt on read.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($value === null || !in_array($key, $this->getEncryptable())) {
            return $value;
        }

        return $this->decryptValue($value);
    }

    /**
     * Override setAttribute to encrypt on write.
     */
    public function setAttribute($key, $value)
    {
        if ($value !== null && in_array($key, $this->getEncryptable())) {
            $value = $this->encryptValue($value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Encrypt a value using Laravel's Crypt facade.
     */
    public function encryptValue(string $value): string
    {
        // Don't double-encrypt if already encrypted
        if ($this->isEncrypted($value)) {
            return $value;
        }

        return Crypt::encryptString($value);
    }

    /**
     * Decrypt a value using Laravel's Crypt facade.
     */
    public function decryptValue(string $value): string
    {
        try {
            if ($this->isEncrypted($value)) {
                return Crypt::decryptString($value);
            }
        } catch (\Throwable $e) {
            // If decryption fails, return the raw value (may not be encrypted)
            \Log::warning('Failed to decrypt attribute', [
                'error' => $e->getMessage(),
            ]);
        }

        return $value;
    }

    /**
     * Check if a value appears to be encrypted.
     * Laravel's encrypted strings start with 'eyJ' (base64 of JSON with 'iv' and 'value' keys).
     */
    public function isEncrypted(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        // Laravel encrypted strings are base64-encoded JSON
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        $json = json_decode($decoded, true);

        return is_array($json) && isset($json['iv']) && isset($json['value']);
    }

    /**
     * Create a scope to search by encrypted column.
     * Note: This performs a full-table scan since encrypted values can't be indexed.
     * Use with caution on large tables.
     */
    public function scopeWhereEncrypted($query, string $column, string $value)
    {
        // For encrypted columns, we need to load all and filter in memory
        // This is a limitation of application-level encryption
        return $query->whereRaw("1=1"); // Placeholder — actual filtering done in collection
    }
}
