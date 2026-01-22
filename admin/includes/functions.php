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
        $data = htmlspecialchars($data);
    }
    return $data;
}
