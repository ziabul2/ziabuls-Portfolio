<?php
require_once __DIR__ . '/../helpers/DatabaseManager.php';

try {
    $db = DatabaseManager::getInstance();
    
    echo "Attempting to create login_attempts table...\n";
    
    $sql = "
    CREATE TABLE IF NOT EXISTS `login_attempts` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `ip_address` VARCHAR(45) NOT NULL,
      `attempt_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `success` BOOLEAN DEFAULT FALSE,
      INDEX `idx_ip_time` (`ip_address`, `attempt_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->executeSQL($sql);
    echo "Table 'login_attempts' created successfully.\n";
    
    // Also verify admins table while we are at it
    $sqlAdmin = "
    CREATE TABLE IF NOT EXISTS `admins` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(50) UNIQUE NOT NULL,
      `password_hash` VARCHAR(255) NOT NULL,
      `display_name` VARCHAR(100),
      `email` VARCHAR(255),
      `avatar` VARCHAR(255),
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->executeSQL($sqlAdmin);
    
    // And admin_sessions
    $sqlSession = "
    CREATE TABLE IF NOT EXISTS `admin_sessions` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `admin_id` INT NOT NULL,
      `session_token` VARCHAR(64) UNIQUE NOT NULL,
      `ip_address` VARCHAR(45),
      `user_agent` TEXT,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `last_activity` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `expires_at` DATETIME NOT NULL,
      FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE,
      INDEX `idx_token` (`session_token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->executeSQL($sqlSession);
    
    // And activity logs
    $sqlLogs = "
    CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `admin_id` INT,
      `action` VARCHAR(50) NOT NULL,
      `details` TEXT,
      `ip_address` VARCHAR(45),
      `user_agent` TEXT,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->executeSQL($sqlLogs);
    echo "All tables verified.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString();
}
