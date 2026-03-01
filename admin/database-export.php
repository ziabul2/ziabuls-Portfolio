<?php
/**
 * Database Export Utility
 * Exports selected tables as SQL dump
 */
require_once __DIR__ . '/../helpers/DatabaseManager.php';
require_once __DIR__ . '/../config/security.php';
requireLogin();

$db = DatabaseManager::getInstance();
$config = require __DIR__ . '/../config/database.php';

$table = $_GET['table'] ?? null;
$action = $_GET['action'] ?? null;

if ($action === 'export' && $table) {
    try {
        // Get table structure
        $stmt = $db->getConnection()->query("SHOW CREATE TABLE `$table`");
        $createTable = $stmt->fetch();
        
        // Get table data
        $stmt = $db->getConnection()->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll();
        
        // Generate SQL dump
        $sql = "-- Export for table: $table\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= $createTable['Create Table'] . ";\n\n";
        
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $values = array_map(function($val) use ($db) {
                    if ($val === null) return 'NULL';
                    return $db->getConnection()->quote($val);
                }, array_values($row));
                
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
        }
        
        // Send as download
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d') . '.sql"');
        echo $sql;
        exit;
        
    } catch (PDOException $e) {
        die('Export failed: ' . $e->getMessage());
    }
}

if ($action === 'export_all') {
    try {
        // Get all tables
        $stmt = $db->getConnection()->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $sql = "-- Full Database Export\n";
        $sql .= "-- Database: {$config['database']}\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            // Get create table statement
            $stmt = $db->getConnection()->query("SHOW CREATE TABLE `$table`");
            $createTable = $stmt->fetch();
            
            $sql .= "\n-- Table: $table\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $createTable['Create Table'] . ";\n\n";
            
            // Get data
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
        
        // Send as download
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $config['database'] . '_' . date('Y-m-d') . '.sql"');
        echo $sql;
        exit;
        
    } catch (PDOException $e) {
        die('Export failed: ' . $e->getMessage());
    }
}

header('Location: manage-database.php');
exit;
