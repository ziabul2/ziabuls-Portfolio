<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/EncryptionHelper.php';
require_once __DIR__ . '/helpers/JsonDbHelper.php';
require_once __DIR__ . '/helpers/AdminAuth.php';

// Test 1: Encryption round-trip
$enc = new EncryptionHelper();
$secret = 'JBSWY3DPEHPK3PXP'; // Example base32 secret
$encrypted = $enc->encrypt($secret);
$decrypted = $enc->decrypt($encrypted);
assert($decrypted === $secret, 'Encryption round-trip FAILED');
echo "[OK] EncryptionHelper: encrypt/decrypt round-trip passed\n";

// Test 2: JsonDbHelper
$db = new JsonDbHelper(__DIR__ . '/data/users.json');
$user = $db->findBy('id', 1);
assert($user !== null, 'User not found in users.json');
echo "[OK] JsonDbHelper: Found user id=1 (" . $user['username'] . ")\n";

// Test 3: AdminAuth instantiation
$auth = new AdminAuth();
$u = $auth->getUser();
assert($u !== null, 'AdminAuth->getUser() returned null');
echo "[OK] AdminAuth: getUser() returned user: " . $u['username'] . "\n";

// Test 4: TwoFactorAuth secret generation
$secret = $auth->generateTfaSecret();
assert(strlen($secret) >= 16, 'Secret too short');
echo "[OK] TwoFactorAuth: generateTfaSecret() = " . $secret . "\n";

echo "\nAll tests passed!\n";
