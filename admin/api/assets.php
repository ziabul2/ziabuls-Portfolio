<?php
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

header('Content-Type: application/json');
$assets = getAvailableAssets(__DIR__ . '/../../assets/');
echo json_encode($assets);
