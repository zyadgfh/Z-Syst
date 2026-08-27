<?php

namespace Tests\Unit;

use App\Traits\EncryptableAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TestEncryptableModel extends Model
{
    use EncryptableAttribute;

    protected $table = 'users';
    protected $fillable = ['name', 'email', 'phone'];
    protected $encryptable = ['phone', 'email'];
}

class EncryptableAttributeTest extends TestCase
{
    use RefreshDatabase;

    public function test_encryptable_returns_configured_attributes(): void
    {
        $model = new TestEncryptableModel();

        $this->assertEquals(['phone', 'email'], $model->getEncryptable());
    }

    public function test_encrypt_value_produces_encrypted_string(): void
    {
        $model = new TestEncryptableModel();

        $encrypted = $model->encryptValue('0501234567');

        $this->assertNotEquals('0501234567', $encrypted);
        $this->assertNotEmpty($encrypted);

        // Verify it can be decrypted
        $decrypted = Crypt::decryptString($encrypted);
        $this->assertEquals('0501234567', $decrypted);
    }

    public function test_decrypt_value_restores_original(): void
    {
        $model = new TestEncryptableModel();

        $encrypted = Crypt::encryptString('test@example.com');
        $decrypted = $model->decryptValue($encrypted);

        $this->assertEquals('test@example.com', $decrypted);
    }

    public function test_is_encrypted_detects_encrypted_strings(): void
    {
        $model = new TestEncryptableModel();

        $encrypted = Crypt::encryptString('hello');
        $this->assertTrue($model->isEncrypted($encrypted));

        $this->assertFalse($model->isEncrypted('plain text'));
        $this->assertFalse($model->isEncrypted(''));
        $this->assertFalse($model->isEncrypted('dGVzdA==')); // base64 but not encrypted JSON
    }

    public function test_double_encryption_is_prevented(): void
    {
        $model = new TestEncryptableModel();

        $encrypted = $model->encryptValue('test');
        $doubleEncrypted = $model->encryptValue($encrypted);

        // Should not double-encrypt
        $this->assertEquals($encrypted, $doubleEncrypted);
    }

    public function test_decrypt_returns_original_on_failure(): void
    {
        $model = new TestEncryptableModel();

        // Not encrypted — should return as-is
        $result = $model->decryptValue('not encrypted');
        $this->assertEquals('not encrypted', $result);
    }
}
