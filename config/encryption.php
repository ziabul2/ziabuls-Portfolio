<?php
/**
 * Application Encryption Configuration
 *
 * SECURITY WARNING: Keep this file private and NEVER commit it to source control.
 * This key is used to encrypt all 2FA secrets stored in users.json.
 * If you rotate this key, all existing 2FA secrets will be invalidated.
 *
 * To regenerate a new key:
 *   php -r "echo bin2hex(random_bytes(32));"
 * Then paste the 64-char hex string as the value of 'key_hex' below.
 *
 * The EncryptionHelper reads 'key_hex' and converts it with hex2bin() → always 32 bytes.
 */
return [
    // 64 hexadecimal characters = 32 bytes = 256-bit AES key
    'key_hex' => 'a3f1d2e4b5c6789012345678901234567890abcdef1234567890abcdef123456',
    'cipher'  => 'aes-256-cbc',
];
