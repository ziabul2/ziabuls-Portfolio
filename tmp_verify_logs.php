<?php
require_once 'helpers/AdminAuth.php';
require_once 'helpers/AuditLogger.php';

session_start();
$_SESSION['admin_token'] = 'test_token';
$_SESSION['admin_data'] = ['id' => 1, 'username' => 'testuser'];

$auth = new AdminAuth();
echo "Triggering Activity Log Migration...\n";
$logs = $auth->getActivityLog(1, 10);
echo "New Activity Log Start: " . substr(file_get_contents('logs/admin_activity.log'), 0, 50) . "...\n";

$audit = new AuditLogger();
echo "Triggering Backup...\n";
$path = $audit->backup('audit');
if ($path && file_exists($path)) {
    echo "Backup Created at: $path\n";
    echo "Backup Content Start: " . substr(file_get_contents($path), 0, 50) . "...\n";
} else {
    echo "Backup Failed!\n";
}
?>
