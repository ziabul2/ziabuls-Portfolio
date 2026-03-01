<?php
/**
 * Database Operations Handler
 * Handles optimize, test connection, and other database actions
 */
require_once __DIR__ . '/../helpers/DatabaseManager.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = DatabaseManager::getInstance();
$action = $_GET['action'] ?? null;

if ($action === 'test_connection') {
    if ($db->testConnection()) {
        setFlashMessage('Database connection is working perfectly! ✓', 'success');
    } else {
        setFlashMessage('Database connection test failed!', 'error');
    }
    header('Location: manage-database.php');
    exit;
}

if ($action === 'optimize_tables') {
    try {
        $stmt = $db->getConnection()->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $optimized = 0;
        foreach ($tables as $table) {
            $db->getConnection()->exec("OPTIMIZE TABLE `$table`");
            $optimized++;
        }
        
        setFlashMessage("Successfully optimized $optimized table(s)!", 'success');
    } catch (PDOException $e) {
        setFlashMessage('Optimization failed: ' . $e->getMessage(), 'error');
    }
    header('Location: manage-database.php');
    exit;
}

if ($action === 'backup_db') {
    try {
        $config = require __DIR__ . '/../config/database.php';
        
        // Create backup directory if doesn't exist
        $backupDir = __DIR__ . '/../database/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Get all tables
        $stmt = $db->getConnection()->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $sql = "-- Database Backup\n";
        $sql .= "-- Database: {$config['database']}\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $stmt = $db->getConnection()->query("SHOW CREATE TABLE `$table`");
            $createTable = $stmt->fetch();
            
            $sql .= "\n-- Table: $table\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $createTable['Create Table'] . ";\n\n";
            
            $stmt = $db->getConnection()->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll();
            
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $values = array_map(function($val) use ($db) {
                        if ($val === null) return 'NULL';
                        return $db->getConnection()->quote($val);
                    }, array_values($row));
                    
                    $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        file_put_contents($backupDir . '/' . $filename, $sql);
        
        setFlashMessage("Database backup created: $filename", 'success');
    } catch (Exception $e) {
        setFlashMessage('Backup failed: ' . $e->getMessage(), 'error');
    }
    header('Location: manage-database.php');
    exit;
}

header('Location: manage-database.php');
exit;
