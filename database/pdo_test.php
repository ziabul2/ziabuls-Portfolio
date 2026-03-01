<?php
// Quick PDO test
echo "Testing PDO...\n";
echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "\n";

if (!extension_loaded('pdo_mysql')) {
    echo "\nPDO MySQL extension is NOT loaded.\n";
    echo "Please enable it in php.ini by uncommenting: extension=pdo_mysql\n";
    exit(1);
}

echo "Attempting connection...\n";

try {
    $pdo = new PDO(
        'mysql:host=sql113.ezyro.com;dbname=ezyro_40986489_aboutblogs;charset=utf8mb4',
        'ezyro_40986489',
        'c5e76e88536',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "SUCCESS! Connected to database.\n";
    
    $stmt = $pdo->query('SELECT 1');
    echo "Query test: PASSED\n";
    
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
