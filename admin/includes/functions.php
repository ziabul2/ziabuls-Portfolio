<?php
/**
 * Admin Panel Functions
 */

/**
 * Returns the path to the portfolio data file
 */
function getPortfolioFilePath() {
    return __DIR__ . '/../../data/portfolio.json';
}

/**
 * Loads data from the portfolio.json file
 */
function getPortfolioData() {
    $filePath = getPortfolioFilePath();
    if (!file_exists($filePath)) {
        return [];
    }
    $json = file_get_contents($filePath);
    return json_decode($json, true) ?: [];
}

/**
 * Saves data to the portfolio.json file with backup
 */
function savePortfolioData($data) {
    if (empty($data)) return false;
    
    $filePath = getPortfolioFilePath();
    
    // Create backup before writing
    if (file_exists($filePath)) {
        copy($filePath, $filePath . '.bak');
    }
    
    // Pretty print JSON and save
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    // Atomic write
    $tempFile = $filePath . '.tmp';
    if (file_put_contents($tempFile, $json) !== false) {
        return rename($tempFile, $filePath);
    }
    
    return false;
}

/**
 * Flash Message Handling
 */
function setFlashMessage($message, $type = 'success') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlashMessage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Input sanitization
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
    } else {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

/**
 * Handles file uploads to the assets directory
 */
function handleFileUpload($file, $targetDir = '../../assets/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
    $fileType = $file['type'];

    if (!in_array($fileType, $allowedTypes)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, WEBP, GIF, and SVG are allowed.'];
    }

    $fileName = basename($file['name']);
    $fileName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $fileName); // Sanitize filename
    $targetPath = $targetDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'assets/' . $fileName;
    }

    return ['error' => 'Failed to move uploaded file.'];
}

/**
 * Scans the assets directory for available images
 */
function getAvailableAssets($dir = '../../assets/') {
    $assets = [];
    if (is_dir($dir)) {
        $files = scandir($dir);
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts)) {
                $assets[] = 'assets/' . $file;
            }
        }
    }
    return $assets;
}
