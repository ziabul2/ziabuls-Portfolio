<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/AdminAuth.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$auth = new AdminAuth();
$error = '';
$lockoutTime = 0;
$ip = $_SERVER['REMOTE_ADDR'];
$ua = $_SERVER['HTTP_USER_AGENT'];

// Check initial lockout state
if ($auth->isLockedOut($ip)) {
    $lockoutTime = $auth->getLockoutRemaining($ip);
    $error = "Too many failed attempts. Please wait.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockoutTime == 0) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $auth->login($username, $password, $ip, $ua);

    if ($result['success']) {
        $_SESSION['admin_token'] = $result['token'];
        $_SESSION['logged_in'] = true; // Legacy support
        regenerateSession();
        header('Location: index.php');
        exit;
    } else {
        $error = $result['error'];
        if (isset($result['lockout']) && $result['lockout']) {
            $lockoutTime = $result['wait'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Admin Panel</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <script>
        // Auto-hide error logic and Timer Logic
        document.addEventListener('DOMContentLoaded', function() {
            // Auto hide error after 2s
            const errorMsg = document.querySelector('.error-msg');
            if (errorMsg) {
                setTimeout(() => {
                    errorMsg.style.transition = "opacity 0.5s ease";
                    errorMsg.style.opacity = "0";
                    setTimeout(() => errorMsg.remove(), 500);
                }, 2000);
            }

            // Lockout Timer Logic
            let lockoutTime = <?php echo $lockoutTime; ?>;
            const btn = document.querySelector('.btn-login');
            const timerDiv = document.getElementById('lockout-timer');
            const timerSpan = document.getElementById('timer-count');
            
            if (lockoutTime > 0) {
                // Ensure UI reflects locked state
                btn.style.display = 'none';
                timerDiv.style.display = 'block';
                document.getElementById('username').disabled = true;
                document.getElementById('password').disabled = true;
                
                const interval = setInterval(() => {
                    lockoutTime--;
                    timerSpan.innerText = lockoutTime;
                    if (lockoutTime <= 0) {
                        clearInterval(interval);
                        // Reload to reset state and clear lockout logic on backend check
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
        
        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div id="lockout-timer" style="display:<?php echo $lockoutTime > 0 ? 'block' : 'none'; ?>; color: #ff6b6b; margin-bottom: 15px; text-align: center;">
            Limit reached. Wait <span id="timer-count"><?php echo $lockoutTime; ?></span>s...
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus <?php echo $lockoutTime > 0 ? 'disabled' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required <?php echo $lockoutTime > 0 ? 'disabled' : ''; ?>>
            </div>
            <button type="submit" class="btn-login" <?php echo $lockoutTime > 0 ? 'style="display:none;"' : ''; ?>>SIGN_IN</button>
        </form>
    </div>
</body>
</html>
