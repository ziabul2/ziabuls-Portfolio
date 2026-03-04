<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/AuditLogger.php';

$audit = new AuditLogger();
startSecureSession();
$auth = new AdminAuth();

$audit->log("Logout", "Admin logged out");

// Call the new Auth logout which cleans up the central session DB
$auth->logout();

header('Location: login.php');
exit;
