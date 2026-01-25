<?php
/**
 * Database Configuration Template
 * Copy this file to database.php and fill in your credentials
 */

return [
    'host' => 'your_mysql_host',
    'database' => 'your_database_name',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
