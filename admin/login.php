<?php
require_once __DIR__ . '/../config/security.php';
$config = require __DIR__ . '/../config/admin.php';

startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === $config['username'] && password_verify($password, $config['password_hash'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user'] = $username;
        $_SESSION['last_activity'] = time();
        
        regenerateSession();
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password';
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
</head>
<body class="login-body">
    <div class="login-card">
        <h2>_Login</h2>
        
        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">SIGN_IN</button>
        </form>
    </div>
</body>
</html>
