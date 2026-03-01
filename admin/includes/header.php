<?php
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../includes/functions.php'; // Need functions to get data
requireLogin();

// Load Admin Settings
$portfolioData = getPortfolioData();
$adminSettings = $portfolioData['admin_settings'] ?? [
    'title' => 'Admin Dashboard',
    'header_text' => 'admin-panel',
    'favicon' => ''
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($adminSettings['title'] . ' | ' . ($portfolioData['seo']['title'] ?? 'ZIMBABU')); ?></title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if(!empty($adminSettings['favicon'])): ?>
        <link rel="icon" href="../<?php echo htmlspecialchars($adminSettings['favicon']); ?>">
    <?php endif; ?>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php" style="text-decoration:none; color: #fff;">
                <h2><span style="color:var(--accent-color)">#</span><?php echo htmlspecialchars($adminSettings['header_text']); ?></h2>
            </a>
        </div>
        <nav class="nav-links" style="display:flex; align-items:center;">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="profile.php" style="display:flex; align-items:center; gap:8px;">
                <?php 
                $avatar = $_SESSION['admin_data']['avatar'] ?? '';
                if ($avatar): ?>
                    <img src="<?php echo htmlspecialchars($avatar); ?>" style="width:24px; height:24px; border-radius:50%; object-fit:cover;">
                <?php else: ?>
                    <i class="fas fa-user-shield"></i>
                <?php endif; ?>
                Account
            </a>
            <a href="logout.php" style="color:var(--error-color)"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>
    <div class="admin-container">
