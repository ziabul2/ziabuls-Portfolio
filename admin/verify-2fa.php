<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/AdminAuth.php';
require_once __DIR__ . '/../helpers/AuditLogger.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}


startSecureSession();

// Allow access only while 2FA is pending (after successful password login).
if (
    empty($_SESSION['2fa_pending']) ||
    $_SESSION['2fa_pending'] !== true ||
    empty($_SESSION['admin_token'])
) {
    header('Location: login.php');
    exit;
}

$auth  = new AdminAuth();
$error = '';

// Check if verify page is temporarily locked out (OTP brute-force protection)
$isOtpLocked     = $auth->isOtpLockedOut();
$otpLockRemaining = $auth->getOtpLockRemaining();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isOtpLocked) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid session token. Please reload and try again.';
    } else {
        $input = preg_replace('/\s+/', '', $_POST['code'] ?? '');

        // ── Try TOTP first ────────────────────────────────────────────────────
        if (preg_match('/^\d{6}$/', $input) && $auth->verifyTfaCode($input)) {
            $auth->clearOtpFailures();

            // Elevate session to fully logged in
            unset($_SESSION['2fa_pending']);
            $_SESSION['logged_in'] = true;

            // Log the completed 2FA login (already handled by registerSession or keep specifically?)
            // Actually, keep this one as it's the FINAL success.
            $auditLogger = new AuditLogger();
            $username = $_SESSION['admin_data']['username'] ?? 'admin';
            $auditLogger->log('Login (2FA Complete)', '2FA OTP verified — full access granted', 'success', $username);

            // Register session in central DB
            $auth->registerSession($_SESSION['admin_token'], $_SESSION['admin_data']['id'] ?? 1, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);

            regenerateSession();
            header('Location: index.php');
            exit;
        }

        // ── Try backup code ───────────────────────────────────────────────────
        if (strlen($input) > 0 && $auth->consumeBackupCode($input)) {
            $auth->clearOtpFailures();

            unset($_SESSION['2fa_pending']);
            $_SESSION['logged_in'] = true;

            // Log backup code usage
            $auditLogger = new AuditLogger();
            $username = $_SESSION['admin_data']['username'] ?? 'admin';
            $auditLogger->log('Login (Backup Code)', 'Recovery backup code used for 2FA login', 'warning', $username);

            // Register session in central DB
            $auth->registerSession($_SESSION['admin_token'], $_SESSION['admin_data']['id'] ?? 1, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);

            regenerateSession();
            header('Location: index.php');
            exit;
        }

        // ── Both failed ───────────────────────────────────────────────────────
        $auth->recordOtpFailure();
        $isOtpLocked     = $auth->isOtpLockedOut();
        $otpLockRemaining = $auth->getOtpLockRemaining();

        if ($isOtpLocked) {
            $error = 'Too many failed attempts. Verification is locked for 30 seconds.';
        } else {
            $error = 'Invalid code. Please check your authenticator app or enter a backup code.';
        }
    }
}

// Generate fresh CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification | Admin Panel</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { display:flex; justify-content:center; align-items:center; min-height:100vh; }
        .tfa-card {
            background: var(--card-bg, #1a1a1a);
            border: 1px solid #333;
            border-radius: 12px;
            padding: 40px 36px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.5);
            text-align: center;
        }
        .tfa-icon { font-size: 2.8rem; color: #61afef; margin-bottom: 16px; }
        .tfa-card h2 { margin: 0 0 8px; font-size: 1.4rem; }
        .tfa-card p  { color:#888; font-size:0.9rem; margin-bottom:24px; line-height:1.5; }
        .code-input {
            text-align:center;
            font-size: 1.6rem !important;
            letter-spacing: 8px;
            font-family: monospace;
            padding: 14px !important;
        }
        .lockout-box {
            background: rgba(224,108,117,0.1);
            border: 1px solid rgba(224,108,117,0.35);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .lockout-box .timer { font-size:1.3rem; font-weight:bold; color:#ff6b6b; font-family:monospace; margin:6px 0; }
        .divider { display:flex; align-items:center; gap:10px; margin:18px 0; color:#555; font-size:0.8rem; }
        .divider::before,.divider::after { content:''; flex:1; border-top:1px solid #333; }
        .backup-input { letter-spacing: 3px; font-family: monospace; font-size: 1rem; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let lockSecs = <?php echo (int)$otpLockRemaining; ?>;
            const timerEl = document.getElementById('lock-timer');
            if (lockSecs > 0 && timerEl) {
                const tick = setInterval(() => {
                    lockSecs--;
                    timerEl.textContent = lockSecs + 's';
                    if (lockSecs <= 0) { clearInterval(tick); location.reload(); }
                }, 1000);
            }
        });
    </script>
</head>
<body class="login-body">
<div class="tfa-card">
    <div class="tfa-icon"><i class="fas fa-mobile-alt"></i></div>
    <h2>Two-Factor Verification</h2>
    <p>Open your authenticator app and enter the 6-digit code, or use one of your backup recovery codes.</p>

    <?php if (!empty($error)): ?>
        <div class="error-msg" style="text-align:left;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($isOtpLocked): ?>
        <div class="lockout-box">
            <div>🔒 Too many failed attempts</div>
            <div class="timer" id="lock-timer"><?php echo $otpLockRemaining; ?>s</div>
            <div style="color:#888;font-size:0.8rem;">Locked for 30 seconds after 5 failed tries.</div>
        </div>
    <?php endif; ?>

    <form method="POST" id="tfaForm">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

        <div class="form-group">
            <label for="code">Authenticator Code or Backup Code</label>
            <input type="text" id="code" name="code"
                   class="code-input"
                   required autofocus
                   maxlength="10"
                   placeholder="000000"
                   autocomplete="one-time-code"
                   <?php echo $isOtpLocked ? 'disabled' : ''; ?>>
        </div>

        <?php if (!$isOtpLocked): ?>
            <button type="submit" class="btn-login" style="width:100%; margin-top:8px;">
                <i class="fas fa-check-circle"></i> Verify &amp; Sign In
            </button>
        <?php endif; ?>
    </form>

    <div class="divider">Need help?</div>

    <a href="logout.php" style="color:#e06c75;text-decoration:none;font-size:0.9rem;">
        <i class="fas fa-sign-out-alt"></i> Cancel and return to login
    </a>
</div>
</body>
</html>
