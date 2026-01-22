<?php
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $result = handleFileUpload($_FILES['file'], __DIR__ . '/../../assets/');
    if (is_string($result)) {
        echo json_encode(['success' => true, 'path' => $result]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unknown error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
}
