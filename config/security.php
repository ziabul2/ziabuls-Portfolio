<?php
/**
 * Security and Authentication helper functions
 */

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session cookie parameters
        session_set_cookie_params([
            'lifetime' => 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

require_once __DIR__ . '/../helpers/AdminAuth.php';

function isLoggedIn() {
    startSecureSession();
    
    if (isset($_SESSION['admin_token'])) {
        try {
            $auth = new AdminAuth();
            $session = $auth->validateSession($_SESSION['admin_token']);
            if ($session) {
                $_SESSION['admin_data'] = $session;
                return true;
            }
        } catch (Exception $e) {
            // Database error or similar
            error_log("Auth Error: " . $e->getMessage());
        }
        
        // Invalid token
        unset($_SESSION['admin_token']);
        unset($_SESSION['admin_data']);
        unset($_SESSION['logged_in']); // Clear legacy flag
    }
    
    return false;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    startSecureSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    startSecureSession();
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    startSecureSession();
    $timeout = 3600; // 1 hour in seconds
    
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > $timeout) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Require login with session timeout check
 */
function requireLoginWithTimeout() {
    if (!isLoggedIn() || !checkSessionTimeout()) {
        header('Location: login.php');
        exit;
    }
}

