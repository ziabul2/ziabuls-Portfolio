<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../helpers/AdminAuth.php';
require_once __DIR__ . '/../config/security.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$flash = getFlashMessage();
$auth  = new AdminAuth();
$user  = $auth->getUser();
$is2FAEnabled = !empty($user['twofa_enabled']);

// Backup codes stored in session for one-time display only
$newBackupCodesDisplay = $_SESSION['new_backup_codes_display'] ?? null;
if ($newBackupCodesDisplay) {
    unset($_SESSION['new_backup_codes_display']); // Show only once
}

// POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request token.', 'error');
        header('Location: setup-2fa.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ----- ENABLE 2FA -----
    if ($action === 'enable') {
        $pendingSecret = $_SESSION['2fa_pending_secret'] ?? '';
        $code          = preg_replace('/\s+/', '', $_POST['code'] ?? '');

        if (empty($pendingSecret) || empty($code)) {
            setFlashMessage('Session expired. Please restart setup.', 'error');
            header('Location: setup-2fa.php');
            exit;
        }

        // Verify OTP using the library directly before committing
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        $tfa = new \RobThree\Auth\TwoFactorAuth('MyCV Admin');

        if (!$tfa->verifyCode($pendingSecret, $code, 2)) {
            setFlashMessage('Invalid code. Your authenticator code did not match. Try again.', 'error');
            header('Location: setup-2fa.php');
            exit;
        }

        // Generate 10 backup codes — display in plaintext once, store hashes
        $plainCodes  = [];
        $hashedCodes = [];
        for ($i = 0; $i < 10; $i++) {
            $raw           = strtoupper(bin2hex(random_bytes(4))); // 8-char hex
            $plainCodes[]  = $raw;
            $hashedCodes[] = password_hash($raw, PASSWORD_DEFAULT);
        }

        $auth->enableTfa($pendingSecret, $hashedCodes);
        unset($_SESSION['2fa_pending_secret']);

        // Store plain codes in session for one-time display on redirect
        $_SESSION['new_backup_codes_display'] = $plainCodes;
        setFlashMessage('Two-Factor Authentication enabled successfully!');
        header('Location: setup-2fa.php');
        exit;
    }

    // ----- DISABLE 2FA -----
    if ($action === 'disable') {
        $password = $_POST['password'] ?? '';
        $code     = preg_replace('/\s+/', '', $_POST['code'] ?? '');

        if ($auth->disableTfa($password, $code)) {
            setFlashMessage('Two-Factor Authentication disabled successfully.');
            header('Location: setup-2fa.php');
            exit;
        } else {
            setFlashMessage('Invalid password or authenticator code.', 'error');
            header('Location: setup-2fa.php');
            exit;
        }
    }
}

// Generate a fresh secret for the setup form (stored in session to survive POST)
$newSecret   = '';
$qrCodeUri   = '';
if (!$is2FAEnabled) {
    if (empty($_SESSION['2fa_pending_secret'])) {
        $_SESSION['2fa_pending_secret'] = $auth->generateTfaSecret();
    }
    $newSecret = $_SESSION['2fa_pending_secret'];
    $label     = ($user['email'] ?? 'admin') . ' (MyCV Admin)';
    $qrCodeUri = $auth->getQrCodeDataUri($label, $newSecret);
}

$remainingCodes = count($user['backup_codes'] ?? []);
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1><i class="fas fa-shield-alt"></i> Two-Factor Authentication (2FA)</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo htmlspecialchars($flash['message']); ?></div>
<?php endif; ?>

<?php /* ---- ONE-TIME BACKUP CODE DISPLAY ---- */
if (!empty($newBackupCodesDisplay)): ?>
<div class="editor-card" style="border-left: 4px solid #e5c07b; margin-bottom: 30px;">
    <h2 style="color:#e5c07b;"><i class="fas fa-key"></i> Save Your Backup Codes</h2>
    <p style="color:#aaa; margin-bottom: 15px;">
        These codes will <strong>only be shown once</strong>. Store them somewhere safe — you can use any of them to log in if you lose access to your authenticator app. Each code can only be used once.
    </p>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: #111; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 1rem; margin-bottom: 15px;">
        <?php foreach ($newBackupCodesDisplay as $bc): ?>
            <div style="background:#1a1a1a; padding: 8px 12px; border-radius: 5px; color: #98c379; letter-spacing: 2px; text-align: center;"><?php echo htmlspecialchars($bc); ?></div>
        <?php endforeach; ?>
    </div>
    <p style="color:#666; font-size: 0.8rem;"><i class="fas fa-exclamation-triangle" style="color:#e5c07b;"></i> Closing this page will permanently hide these codes.</p>
</div>
<?php endif; ?>

<div class="editor-card" style="max-width: 620px; margin: 0 auto; <?php echo $is2FAEnabled ? 'border-left: 4px solid #98c379;' : 'border-left: 4px solid #61afef;'; ?>">
    <?php if (!$is2FAEnabled): ?>
        <h2><i class="fas fa-mobile-alt"></i> Enable Authenticator App</h2>
        <p style="color:#aaa; font-size:0.95rem; line-height:1.6; margin-bottom:20px;">
            Link your admin account to Google Authenticator (or any TOTP app like Authy). Once enabled, every login requires a 6-digit code from the app.
        </p>

        <div style="background:#111; padding: 20px; border-radius: 8px; text-align:center; margin-bottom: 25px;">
            <p style="margin-bottom: 15px; font-weight: bold; color:#ddd;">Step 1 — Scan the QR Code</p>
            <img src="<?php echo $qrCodeUri; ?>" alt="QR Code" style="background:#fff; padding:10px; border-radius:6px; border: 1px solid #333;">
            <p style="margin-top:15px; font-size:0.8rem; color:#666;">
                Or manually enter this secret key into your app:<br>
                <strong style="color:#61afef; font-family:monospace; letter-spacing:2px; font-size:1rem; word-break:break-all;"><?php echo htmlspecialchars($newSecret); ?></strong>
            </p>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="enable">

            <div class="form-group">
                <label for="code">Step 2 — Enter the 6-digit code to verify</label>
                <input type="text" id="code" name="code" required maxlength="6" pattern="\d{6}"
                       placeholder="000000" style="text-align:center; font-size:1.3rem; letter-spacing:6px;" autocomplete="one-time-code">
            </div>

            <button type="submit" class="btn-login" style="width:100%; margin-top:10px;">
                <i class="fas fa-lock"></i> Enable 2FA &amp; Generate Backup Codes
            </button>
        </form>

    <?php else: ?>
        <h2><i class="fas fa-check-circle" style="color:#98c379;"></i> 2FA is Active</h2>
        <p style="color:#aaa; font-size:0.9rem; line-height:1.5; margin-bottom:20px;">
            Your account is protected by Two-Factor Authentication.
            You have <strong style="color:#e5c07b;"><?php echo $remainingCodes; ?> backup code(s)</strong> remaining.
        </p>

        <form method="POST" style="background:rgba(224,108,117,0.05); padding:20px; border-radius:8px; border:1px solid rgba(224,108,117,0.3);">
            <h3 style="color:#e06c75; margin:0 0 15px; font-size:1.05rem;"><i class="fas fa-power-off"></i> Disable 2FA</h3>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="disable">

            <div class="form-group">
                <label for="password">Admin Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label for="code">Current Authenticator Code</label>
                <input type="text" id="code" name="code" required maxlength="6" pattern="\d{6}"
                       placeholder="000000" style="text-align:center; font-size:1.2rem; letter-spacing:5px;" autocomplete="one-time-code">
            </div>

            <button type="submit" class="btn-remove" style="width:100%; position:static;">
                <i class="fas fa-shield-alt"></i> Disable 2FA
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
