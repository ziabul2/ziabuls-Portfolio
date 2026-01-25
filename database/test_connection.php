<?php
/**
 * Database Connection Test
 * Run this file to verify database connection
 */

require_once __DIR__ . '/../helpers/DatabaseManager.php';

echo "Testing Database Connection...\n\n";

try {
    $db = DatabaseManager::getInstance();
    
    if ($db->testConnection()) {
        echo "✓ Database connection successful!\n";
        echo "✓ Connected to: " . require(__DIR__ . '/../config/database.php')['database'] . "\n\n";
        
        // Test schema creation
        echo "Creating database schema...\n";
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        
        // Split by semicolon to execute each statement separately
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $db->executeSQL($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            }
        }
        
        echo "\n✓ Database schema created successfully!\n";
    } else {
        echo "✗ Database connection failed!\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
