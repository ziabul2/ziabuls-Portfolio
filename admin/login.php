<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/AdminAuth.php';
require_once __DIR__ . '/../helpers/AuditLogger.php';

$audit = new AuditLogger();

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$auth    = new AdminAuth();
$error   = '';
$success = '';
$lockoutTime  = 0;
$ip      = $_SERVER['REMOTE_ADDR'];
$ua      = $_SERVER['HTTP_USER_AGENT'];

// Load reCAPTCHA site key for front-end
$adminConfig  = require __DIR__ . '/../config/admin.php';
$siteKey      = $adminConfig['recaptcha_site_key'] ?? '';
$devMode      = empty($siteKey) || $siteKey === 'YOUR_RECAPTCHA_SITE_KEY';

// Check initial lockout state before any POST
if ($auth->isLockedOut($ip)) {
    $lockoutTime = $auth->getLockoutRemaining($ip);
    $error       = "Too many failed attempts. Please wait.";
}

// Current failure count (for reCAPTCHA display)
$failureCount  = $auth->getFailureCount($ip);
$showCaptcha   = !$devMode && $failureCount >= 1 && $lockoutTime === 0;

function generateMathCaptcha() {
    $n1 = rand(1, 9); $n2 = rand(1, 9);
    $op = rand(0, 1) ? '+' : '-';
    if ($op === '-' && $n1 < $n2) { $t = $n1; $n1 = $n2; $n2 = $t; }
    $_SESSION['math_captcha_ans'] = $op === '+' ? ($n1 + $n2) : ($n1 - $n2);
    $_SESSION['math_captcha_str'] = "$n1 $op $n2 = ?";
}

if (!$showCaptcha && $lockoutTime == 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    generateMathCaptcha();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockoutTime == 0) {
    $username       = trim($_POST['username'] ?? '');
    $password       = $_POST['password'] ?? '';
    $recaptchaToken = $_POST['g-recaptcha-response'] ?? null;
    $mathAns        = $_POST['math_captcha_ans'] ?? null;
    $expectedAns    = $_SESSION['math_captcha_ans'] ?? null;

    $result = $auth->login($username, $password, $ip, $ua, $recaptchaToken, $mathAns, $expectedAns);

    if ($result['success']) {
        unset($_SESSION['math_captcha_ans'], $_SESSION['math_captcha_str']);

        // Log the attempt in the security DB FIRST (before redirect)
        $audit->logLoginAttempt($username, $password, true);

        if (!empty($result['requires_2fa']) && $result['requires_2fa'] === true) {
            // Log the password-ok event even though 2FA still pending
            $audit->log('Login (2FA Pending)', "Password verified. 2FA required.", 'success', $username);
            header('Location: verify-2fa.php');
            exit;
        }

        // Session already set by AdminAuth — now log with correct username
        $_SESSION['admin_token']            = $result['token'];
        $_SESSION['logged_in']              = true;
        $_SESSION['admin_data']['username'] = $username;
        $audit->log('Login', 'Admin logged in successfully', 'success', $username);
        regenerateSession();
        header('Location: index.php');
        exit;
    } else {
        // Record the failed attempt in the dedicated security log
        $audit->logLoginAttempt($username, $password, false);
        $audit->log('Login Failed', "Username tried: $username", 'failed');
        $error = $result['error'];

        if (!empty($result['lockout']) && $result['lockout']) {
            $lockoutTime = $result['wait'];
        }

        // Update captcha state after failure
        $failureCount = $auth->getFailureCount($ip);
        $showCaptcha  = !$devMode && ($result['show_captcha'] ?? false);
        if (!$showCaptcha && $lockoutTime == 0) {
            generateMathCaptcha();
        }
    }
}

/* ---- human-readable lockout duration for the UI ---- */
function formatWait($secs) {
    if ($secs >= 3600) {
        $h = round($secs / 3600, 1);
        return "{$h} hour(s)";
    } elseif ($secs >= 60) {
        $m = ceil($secs / 60);
        return "{$m} minute(s)";
    }
    return "{$secs} second(s)";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Admin Panel</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <?php if ($showCaptcha): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <style>
        .security-badge {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.72rem; color: #666; margin-top: 18px;
            justify-content: center; border-top: 1px solid #2a2a2a; padding-top: 12px;
        }
        .attempt-bar {
            display: flex; gap: 5px; margin: 12px 0;
        }
        .attempt-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: #333; border: 1px solid #555;
        }
        .attempt-dot.used  { background: #e06c75; border-color: #e06c75; }
        .attempt-dot.warn  { background: #e5c07b; border-color: #e5c07b; }
        .lockout-box {
            background: rgba(224,108,117,0.12);
            border: 1px solid rgba(224,108,117,0.3);
            border-radius: 8px; padding: 14px 16px;
            text-align: center; margin-bottom: 16px;
        }
        .lockout-box .lock-icon { font-size: 1.6rem; margin-bottom: 6px; }
        .lockout-box p { color: #e06c75; margin: 0; font-size: 0.85rem; }
        .lockout-box .timer-big {
            font-size: 1.5rem; font-weight: bold; color: #ff6b6b;
            font-family: monospace; margin: 6px 0;
        }
        .recaptcha-wrap { margin: 14px 0; display: flex; justify-content: center; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide flash error (only short messages)
            const errorMsg = document.querySelector('.error-msg');
            if (errorMsg && !errorMsg.classList.contains('persistent')) {
                setTimeout(() => {
                    errorMsg.style.transition = "opacity 0.5s ease";
                    errorMsg.style.opacity = "0";
                    setTimeout(() => errorMsg.remove(), 500);
                }, 3500);
            }

            // Lockout countdown timer
            let lockoutSecs = <?php echo (int)$lockoutTime; ?>;
            const timerEl   = document.getElementById('timer-count');
            const timerLbl  = document.getElementById('timer-label');

            if (lockoutSecs > 0 && timerEl) {
                const tick = setInterval(() => {
                    lockoutSecs--;
                    // Format nicely
                    if (lockoutSecs >= 60) {
                        const m = Math.floor(lockoutSecs / 60);
                        const s = lockoutSecs % 60;
                        timerEl.textContent = m + 'm ' + String(s).padStart(2,'0') + 's';
                    } else {
                        timerEl.textContent = lockoutSecs + 's';
                    }
                    if (lockoutSecs <= 0) {
                        clearInterval(tick);
                        window.location.href = window.location.pathname;
                    }
                }, 1000);
            }
        });
    </script>
</head>
<body class="login-body">
    <div class="login-card">
        <h2>_Login</h2>

        <?php /* ---- attempt indicator text ---- */ ?>
        <div style="font-size: 0.8rem; color: #888; text-align: center; margin-bottom: 12px;">
            <?php if ($failureCount > 0): ?>
                <span style="color:#e06c75;">Failed attempts: <?php echo $failureCount; ?></span>
            <?php else: ?>
                Login attempts are monitored.
            <?php endif; ?>
        </div>

        <?php /* ---- lockout banner ---- */
        if ($lockoutTime > 0): ?>
            <div class="lockout-box">
                <div class="lock-icon">🔒</div>
                <p>Account temporarily locked</p>
                <div class="timer-big" id="timer-count"><?php
                    echo $lockoutTime >= 60
                        ? floor($lockoutTime/60).'m '.str_pad($lockoutTime%60,2,'0',STR_PAD_LEFT).'s'
                        : $lockoutTime.'s';
                ?></div>
                <p style="font-size:0.75rem;color:#999;">
                    Locked for <?php echo formatWait($lockoutTime); ?> &mdash; too many failed attempts.
                </p>
            </div>
        <?php endif; ?>

        <?php if ($error && $lockoutTime == 0): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus
                    <?php echo $lockoutTime > 0 ? 'disabled' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                    <?php echo $lockoutTime > 0 ? 'disabled' : ''; ?>>
            </div>

            <?php /* ---- Captcha Section ---- */
            if ($showCaptcha): ?>
                <div class="form-group" style="text-align: center; margin-bottom: 15px;">
                    <span style="font-size: 0.85rem; color: #e5c07b;">Anti-bot test required.</span>
                </div>
                <div class="recaptcha-wrap">
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($siteKey); ?>"></div>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label for="math_captcha_ans">Security: <?php echo $_SESSION['math_captcha_str'] ?? '0 + 0 = ?'; ?></label>
                    <input type="number" id="math_captcha_ans" name="math_captcha_ans" required
                        <?php echo $lockoutTime > 0 ? 'disabled' : ''; ?>>
                </div>
                <?php if ($failureCount >= 1 && $devMode): ?>
                    <div style="font-size:0.75rem;color:#888;text-align:center;margin:10px 0;padding:8px;background:rgba(97,175,239,0.08);border-radius:6px;">
                        <i>reCAPTCHA active in production. Using Math solver fallback.</i>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($lockoutTime == 0): ?>
                <button type="submit" class="btn-login" id="submitBtn">SIGN_IN</button>
            <?php endif; ?>
        </form>

        <!-- <div class="security-badge">
            🛡️ IP-rated · Lockout protection · reCAPTCHA enabled
        </div> -->
    </div>
</body>
</html>
