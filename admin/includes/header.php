<?php
require_once __DIR__ . '/../../config/security.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ZIMBABU</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">
            <h2><span style="color:var(--accent-color)">#</span>admin-panel</h2>
        </div>
        <nav class="nav-links">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php" style="color:var(--error-color)"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>
    <div class="admin-container">
