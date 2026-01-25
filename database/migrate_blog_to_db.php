<?php
/**
 * Blog Migration Script - Migrate blog posts from JSON to MySQL
 * This is a one-time migration script
 */

require_once __DIR__ . '/../helpers/DatabaseManager.php';
require_once __DIR__ . '/../helpers/FileStorageManager.php';

echo "=== Blog Migration Script ===\n\n";

try {
    // Initialize managers
    $db = DatabaseManager::getInstance();
    $fileStorage = new FileStorageManager(__DIR__ . '/../data/portfolio.json');
    
    echo "✓ Managers initialized\n";
    
    // Get current blog posts from JSON
    $data = $fileStorage->getData();
    $blogPosts = $data['blog_posts'] ?? [];
    
    if (empty($blogPosts)) {
        echo "No blog posts found in portfolio.json. Migration not needed.\n";
        exit(0);
    }
    
    echo "Found " . count($blogPosts) . " blog post(s) to migrate\n\n";
    
    // Create backup before migration
    echo "Creating backup before migration...\n";
    $backupFile = $fileStorage->createBackup();
    echo "✓ Backup created: " . basename($backupFile) . "\n\n";
    
    // Begin database transaction
    $db->beginTransaction();
    
    $migrated = 0;
    $errors = 0;
    
    foreach ($blogPosts as $post) {
        echo "Migrating: " . $post['title'] . " (ID: " . $post['id'] . ")... ";
        
        try {
            // Check if post already exists
            $existing = $db->getPost($post['id']);
            
            if ($existing) {
                echo "SKIP (already exists)\n";
                continue;
            }
            
            // Migrate post to database
            $result = $db->createPost([
                'id' => $post['id'],
                'title' => $post['title'],
                'summary' => $post['summary'] ?? '',
                'content' => $post['content'] ?? '',
                'image' => $post['image'] ?? '',
                'status' => $post['status'] ?? 'published',
                'author_id' => 1
            ]);
            
            if ($result) {
                echo "✓ SUCCESS\n";
                $migrated++;
            } else {
                echo "✗ FAILED\n";
                $errors++;
            }
        } catch (Exception $e) {
            echo "✗ ERROR: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    if ($errors > 0) {
        echo "\n⚠ Migration completed with errors. Rolling back...\n";
        $db->rollback();
        echo "Changes rolled back. Please review errors and try again.\n";
        exit(1);
    }
    
    // Commit transaction
    $db->commit();
    echo "\n✓ Database transaction committed\n";
    
    // Remove blog_posts from JSON file (keep it in database only)
    echo "\nRemoving blog posts from portfolio.json...\n";
    unset($data['blog_posts']);
    $fileStorage->updateSections($data);
    echo "✓ Blog posts removed from JSON file\n";
    
    echo "\n=== Migration Summary ===\n";
    echo "Migrated: $migrated post(s)\n";
    echo "Skipped: " . (count($blogPosts) - $migrated - $errors) . " post(s)\n";
    echo "Errors: $errors\n";
    echo "\nBackup file: " . basename($backupFile) . "\n";
    echo "\n✓ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    if (isset($db)) {
        $db->rollback();
        echo "Database changes rolled back.\n";
    }
    exit(1);
}
