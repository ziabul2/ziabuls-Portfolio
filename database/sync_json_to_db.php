<?php
/**
 * Sync Script: JSON -> MySQL
 * Populates the database with existing blog posts from portfolio.json.
 */

require_once __DIR__ . '/../admin/includes/functions.php';
require_once __DIR__ . '/../helpers/DatabaseManager.php';
require_once __DIR__ . '/../helpers/BlogManager.php';

echo "Starting Sync Process...\n";

// 1. Ensure Table Exists
try {
    $db = DatabaseManager::getInstance();
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Simple split by command (this is naive, but works for simple schema)
    $commands = explode(';', $sql);
    foreach ($commands as $command) {
        if (trim($command)) {
            $db->executeSQL($command);
        }
    }
    echo "Schema ensured.\n";
} catch (Exception $e) {
    echo "Error checking schema: " . $e->getMessage() . "\n";
    die("Database not ready.\n");
}

// 2. Load JSON Data
$blogManager = BlogManager::getInstance();
$posts = $blogManager->getPosts(); // Gets from JSON

echo "Found " . count($posts) . " posts in JSON.\n";

// 3. Sync Each Post
$success = 0;
$fail = 0;

foreach ($posts as $post) {
    echo "Syncing: " . $post['title'] . " (" . $post['id'] . ")... ";
    
    // Adapt data structure if needed
    // JSON 'date' -> DB 'created_at' logic is handled inside syncToDatabase kinda, 
    // but lets be explicit here or check logic.
    // BlogManager::syncToDatabase maps basic fields.
    // We should ensure 'created_at' is set from 'date' if possible.
    
    // We can use the public syncToDatabase method I added
    try {
        $blogManager->syncToDatabase($post);
        // Explicitly set created_at from date if present, as syncToDatabase might default to NOW()
        // Doing a direct update for date accuracy if required
        if (isset($post['date'])) {
            $created = date('Y-m-d H:i:s', strtotime($post['date']));
            $db->executeSQL("UPDATE blog_posts SET created_at = '$created' WHERE id = '{$post['id']}'");
        }
        
        echo "OK\n";
        $success++;
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        $fail++;
    }
}

echo "Sync Complete. Success: $success, Failed: $fail\n";
