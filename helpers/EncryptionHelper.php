<?php
/**
 * AES-256-CBC Encryption/Decryption Utility
 *
 * Encrypts strings as: base64( IV + ciphertext )
 * IV is randomly generated per encryption, prepended to the ciphertext.
 */
class EncryptionHelper
{
    private string $key;
    private string $cipher = 'aes-256-cbc';

    public function __construct()
    {
        $config = require __DIR__ . '/../config/encryption.php';

        // Support hex key (64 hex chars → 32 bytes) or legacy base64 key
        if (!empty($config['key_hex'])) {
            $rawKey = hex2bin($config['key_hex']);
        } else {
            $rawKey = base64_decode($config['key'] ?? '');
        }

        if (strlen($rawKey) !== 32) {
            throw new RuntimeException(
                'Encryption key must be 32 bytes (256-bit). Got ' . strlen($rawKey) . ' bytes.'
            );
        }

        $this->key    = $rawKey;
        $this->cipher = $config['cipher'] ?? 'aes-256-cbc';
    }

    /**
     * Encrypt a plaintext string.
     *
     * @param  string $plaintext
     * @return string base64-encoded IV + ciphertext
     * @throws RuntimeException
     */
    public function encrypt(string $plaintext): string
    {
        $ivLen = openssl_cipher_iv_length($this->cipher);
        $iv    = random_bytes($ivLen);

        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed.');
        }

        return base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypt a previously encrypted string.
     *
     * @param  string $encoded base64-encoded IV + ciphertext
     * @return string|false Plaintext on success, false on failure
     */
    public function decrypt(string $encoded): string|false
    {
        $raw   = base64_decode($encoded, true);
        if ($raw === false) {
            return false;
        }

        $ivLen      = openssl_cipher_iv_length($this->cipher);
        $iv         = substr($raw, 0, $ivLen);
        $ciphertext = substr($raw, $ivLen);

        if (strlen($iv) !== $ivLen || $ciphertext === '') {
            return false;
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $plaintext;
    }
}
