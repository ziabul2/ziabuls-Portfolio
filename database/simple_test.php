<?php
/**
 * Simple test to check if database connection works
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Database Connection ===\n\n";

// Test 1: Check if PDO MySQL is available
echo "1. Checking PDO MySQL extension... ";
if (extension_loaded('pdo_mysql')) {
    echo "✓ Available\n";
} else {
    echo "✗ NOT available - Please enable pdo_mysql extension\n";
    exit(1);
}

// Test 2: Load config
echo "2. Loading database configuration... ";
try {
    $config = require __DIR__ . '/../config/database.php';
    echo "✓ Config loaded\n";
    echo "   Host: {$config['host']}\n";
    echo "   Database: {$config['database']}\n";
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Try to connect
echo "3. Attempting database connection... ";
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['database'],
        $config['charset']
    );
    
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        $config['options']
    );
    
    echo "✓ Connected successfully!\n";
} catch (PDOException $e) {
    echo "✗ Connection failed\n";
    echo "   Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Test query
echo "4. Testing query... ";
try {
    $stmt = $pdo->query('SELECT 1 as test');
    $result = $stmt->fetch();
    if ($result && $result['test'] == 1) {
        echo "✓ Query successful\n";
    } else {
        echo "✗ Query returned unexpected result\n";
    }
} catch (PDOException $e) {
    echo "✗ Query failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== All Tests Passed! ===\n";
echo "Database connection is working correctly.\n";
