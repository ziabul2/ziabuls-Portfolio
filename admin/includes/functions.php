<?php
/**
 * Admin Panel Functions
 */

// Set Timezone for Bangladesh (User Request)
date_default_timezone_set('Asia/Dhaka');


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
require_once __DIR__ . '/../../helpers/BackupManager.php';

/**
 * Saves data to the portfolio.json file with backup
 */
function savePortfolioData($data) {
    if (empty($data)) return false;
    
    $filePath = getPortfolioFilePath();
    $backupManager = new BackupManager($filePath);
    
    // 1. Auto-Backup BEFORE saving (Professional Safety)
    $backupManager->createAutoBackup();
    
    // 2. Cleanup Old Backups (Retention Policy: Keep last 2)
    $backupManager->cleanupOldBackups(2);
    
    // 3. Save Data potentially using BackupManager's safe save (which also does a temp backup internally usually, but we made it explicit)
    return $backupManager->saveSafely($data);
}

/**
 * Get list of available backups
 */
function getBackups() {
    $filePath = getPortfolioFilePath();
    $backupManager = new BackupManager($filePath);
    return $backupManager->getBackups();
}

/**
 * Restore a backup
 */
function restoreBackup($filename) {
    $filePath = getPortfolioFilePath();
    $backupManager = new BackupManager($filePath);
    return $backupManager->restoreBackup($filename);
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
 * Resizes and compresses an image to a maximum width to save space.
 */
function resizeAndCompressImage($sourcePath, $destinationPath, $maxWidth = 800, $quality = 85) {
    $info = getimagesize($sourcePath);
    if (!$info) return false;

    list($width, $height) = $info;
    $mime = $info['mime'];

    // Don't upscale
    if ($width > $maxWidth) {
        $ratio = $maxWidth / $width;
        $newWidth = $maxWidth;
        $newHeight = (int)($height * $ratio);
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    $image = imagecreatetruecolor($newWidth, $newHeight);
    
    // Handle transparency for PNG/WEBP/GIF
    if ($mime == 'image/png' || $mime == 'image/webp' || $mime == 'image/gif') {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefilledrectangle($image, 0, 0, $newWidth, $newHeight, $transparent);
    }

    $source = null;
    switch ($mime) {
        case 'image/jpeg': $source = imagecreatefromjpeg($sourcePath); break;
        case 'image/png': $source = imagecreatefrompng($sourcePath); break;
        case 'image/webp': $source = imagecreatefromwebp($sourcePath); break;
        case 'image/gif': $source = imagecreatefromgif($sourcePath); break;
    }

    if (!$source) return false;

    imagecopyresampled($image, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $success = false;
    switch ($mime) {
        case 'image/jpeg': $success = imagejpeg($image, $destinationPath, $quality); break;
        case 'image/png': 
            // Quality for PNG is 0-9. 85 maps roughly to compression level 8
            $success = imagepng($image, $destinationPath, 8); 
            break;
        case 'image/webp': $success = imagewebp($image, $destinationPath, $quality); break;
        case 'image/gif': $success = imagegif($image, $destinationPath); break;
    }

    imagedestroy($image);
    imagedestroy($source);

    return $success;
}

/**
 * Handles file uploads to the assets directory with compression
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
        // Compress the image if it is not an SVG
        if ($fileType !== 'image/svg+xml') {
            resizeAndCompressImage($targetPath, $targetPath, 800, 80);
        }
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
