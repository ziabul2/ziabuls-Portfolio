<?php
/**
 * Backup API Endpoint
 * Handles AJAX requests for backup operations
 */

require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../helpers/BackupManager.php';

requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$filename = $_GET['filename'] ?? $_POST['filename'] ?? null;

// Response structure
$response = [
    'success' => false,
    'message' => 'Unknown error',
    'data' => null
];

try {
    $filePath = getPortfolioFilePath();
    $backupManager = new BackupManager($filePath);

    switch ($action) {
        case 'list':
            // Get list of all backups
            $backups = $backupManager->getBackups();
            $stats = $backupManager->getBackupStats();
            
            $response['success'] = true;
            $response['message'] = 'Backups retrieved successfully';
            $response['data'] = [
                'backups' => $backups,
                'stats' => $stats,
                'count' => count($backups)
            ];
            break;

        case 'get_stats':
            // Get backup statistics
            $stats = $backupManager->getBackupStats();
            
            $response['success'] = true;
            $response['message'] = 'Statistics retrieved successfully';
            $response['data'] = $stats;
            break;

        case 'get_details':
            // Get details about a specific backup
            if (!$filename) {
                throw new Exception('Filename is required');
            }

            $details = $backupManager->getBackupDetails($filename);
            if (!$details) {
                throw new Exception('Backup not found');
            }

            $response['success'] = true;
            $response['message'] = 'Backup details retrieved';
            $response['data'] = $details;
            break;

        case 'restore':
            // Restore a backup
            if (!$filename) {
                throw new Exception('Filename is required');
            }

            if ($backupManager->restoreBackup($filename)) {
                $response['success'] = true;
                $response['message'] = 'Backup restored successfully. A safety backup of the previous state was created.';
            } else {
                throw new Exception('Failed to restore backup');
            }
            break;

        case 'delete':
            // Delete a backup
            if (!$filename) {
                throw new Exception('Filename is required');
            }

            if ($backupManager->deleteBackup($filename)) {
                $response['success'] = true;
                $response['message'] = 'Backup deleted successfully';
            } else {
                throw new Exception('Failed to delete backup');
            }
            break;

        case 'import':
            // Import a backup file
            if (!isset($_FILES['backup_file'])) {
                throw new Exception('No file provided');
            }

            $result = $backupManager->importBackup($_FILES['backup_file']);
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Backup imported successfully';
                $response['data'] = [
                    'filename' => $result
                ];
            } else {
                throw new Exception('Failed to import backup. Ensure the file is valid JSON.');
            }
            break;

        case 'create':
            // Create a new backup
            $backupPath = $backupManager->createBackup();
            if ($backupPath) {
                $response['success'] = true;
                $response['message'] = 'Backup created successfully';
                $response['data'] = [
                    'path' => $backupPath,
                    'filename' => basename($backupPath)
                ];
            } else {
                throw new Exception('Failed to create backup');
            }
            break;

        case 'cleanup':
            // Cleanup old backups
            $keepCount = (int)($_POST['keep_count'] ?? 10);
            if ($keepCount < 1) {
                throw new Exception('Invalid keep count');
            }

            $deleted = $backupManager->cleanupOldBackups($keepCount);
            $response['success'] = true;
            $response['message'] = "Cleanup completed. Deleted $deleted backup(s).";
            $response['data'] = [
                'deleted_count' => $deleted,
                'remaining' => count($backupManager->getBackups())
            ];
            break;

        case 'export':
            // Export a backup file for download
            if (!$filename) {
                throw new Exception('Filename is required');
            }

            $backup = $backupManager->exportBackup($filename);
            if (!$backup) {
                throw new Exception('Backup not found');
            }

            // Send file for download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
            readfile($backup['path']);
            exit;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
