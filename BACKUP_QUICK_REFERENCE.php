<?php
/**
 * BACKUP SYSTEM - QUICK REFERENCE GUIDE
 * 
 * This file documents all the backup system functionality
 * and how to use it in your project.
 */

/**
 * ═══════════════════════════════════════════════════════════════
 * HOW TO ACCESS THE BACKUP MANAGEMENT INTERFACE
 * ═══════════════════════════════════════════════════════════════
 */

// URL: http://localhost/cv/admin/manage-backups.php
// Navigation: Admin Dashboard > Backups Card > Manage Backups

/**
 * ═══════════════════════════════════════════════════════════════
 * USING BACKUPMANAGER IN YOUR CODE
 * ═══════════════════════════════════════════════════════════════
 */

// 1. INCLUDE THE CLASS
require_once 'helpers/BackupManager.php';

// 2. INITIALIZE
$backupManager = new BackupManager('/path/to/portfolio.json');

// 3. USE METHODS

// Create a new backup
$backupPath = $backupManager->createBackup();
if ($backupPath) {
    echo "Backup created: " . $backupPath;
}

// Get all backups
$backups = $backupManager->getBackups();
foreach ($backups as $backup) {
    echo $backup['filename'] . " - " . $backup['size'] . " bytes";
}

// Get backup statistics
$stats = $backupManager->getBackupStats();
echo "Total backups: " . $stats['count'];
echo "Total size: " . $stats['total_size_readable'];
echo "Latest: " . date('Y-m-d H:i:s', $stats['newest']['created']);
echo "Oldest: " . date('Y-m-d H:i:s', $stats['oldest']['created']);

// Get details about a specific backup
$details = $backupManager->getBackupDetails('portfolio_2026-01-22_09-36-11.json');
if ($details) {
    echo "File: " . $details['filename'];
    echo "Size: " . $details['size_readable'];
    echo "Created: " . $details['created_readable'];
}

// Restore a backup (creates safety backup first)
$success = $backupManager->restoreBackup('portfolio_2026-01-22_09-36-11.json');
if ($success) {
    echo "Backup restored successfully!";
}

// Delete a backup
$deleted = $backupManager->deleteBackup('portfolio_2026-01-22_09-36-11.json');
if ($deleted) {
    echo "Backup deleted!";
}

// Export backup (for download)
$backup = $backupManager->exportBackup('portfolio_2026-01-22_09-36-11.json');
if ($backup) {
    // Use in HTTP response:
    // header('Content-Type: application/json');
    // header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
    // readfile($backup['path']);
}

// Import backup from file upload
$result = $backupManager->importBackup($_FILES['backup_file']);
if ($result) {
    echo "Imported as: " . $result; // Returns filename
}

// Clean up old backups (keep only 10 most recent)
$deleted_count = $backupManager->cleanupOldBackups(10);
echo "Deleted $deleted_count old backups";

/**
 * ═══════════════════════════════════════════════════════════════
 * API ENDPOINTS (AJAX)
 * ═══════════════════════════════════════════════════════════════
 */

// GET list of all backups with stats
// URL: admin/api/backups.php?action=list
// Response: { success, message, data: { backups, stats, count } }

// GET backup statistics only
// URL: admin/api/backups.php?action=get_stats
// Response: { success, message, data: { count, total_size, ... } }

// GET details about specific backup
// URL: admin/api/backups.php?action=get_details&filename=portfolio_2026-01-22_09-36-11.json
// Response: { success, message, data: { filename, created, size, ... } }

// POST create new backup
// URL: admin/api/backups.php?action=create (POST)
// Response: { success, message, data: { path, filename } }

// POST restore backup
// URL: admin/api/backups.php?action=restore (POST)
// Data: { filename }
// Response: { success, message }

// POST delete backup
// URL: admin/api/backups.php?action=delete (POST)
// Data: { filename }
// Response: { success, message }

// POST import backup
// URL: admin/api/backups.php?action=import (POST with FILES)
// Files: backup_file (JSON)
// Response: { success, message, data: { filename } }

// POST cleanup old backups
// URL: admin/api/backups.php?action=cleanup (POST)
// Data: { keep_count: 10 }
// Response: { success, message, data: { deleted_count, remaining } }

// GET export backup for download
// URL: admin/api/backups.php?action=export&filename=portfolio_2026-01-22_09-36-11.json
// Response: File download (JSON)

/**
 * ═══════════════════════════════════════════════════════════════
 * EXAMPLE: JAVASCRIPT/AJAX USAGE
 * ═══════════════════════════════════════════════════════════════
 */

/*
// Get backup statistics
fetch('admin/api/backups.php?action=get_stats')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Total backups:', data.data.count);
            console.log('Total size:', data.data.total_size_readable);
        }
    });

// Create a new backup
fetch('admin/api/backups.php?action=create', {
    method: 'POST'
})
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Backup created:', data.data.filename);
        }
    });

// Restore a backup
fetch('admin/api/backups.php?action=restore', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'filename=portfolio_2026-01-22_09-36-11.json'
})
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Backup restored!');
            location.reload();
        }
    });

// Import backup
const formData = new FormData();
formData.append('action', 'import');
formData.append('backup_file', fileInput.files[0]);

fetch('admin/api/backups.php', {
    method: 'POST',
    body: formData
})
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Imported:', data.data.filename);
        }
    });
*/

/**
 * ═══════════════════════════════════════════════════════════════
 * FILE STRUCTURE
 * ═══════════════════════════════════════════════════════════════
 */

/*
data/
├── portfolio.json                       (Main data file)
├── portfolio.json.bak                   (Backup file)
└── backups/                             (Backup directory)
    ├── portfolio_2026-01-22_09-36-11.json
    ├── portfolio_2026-01-22_10-45-22.json
    ├── portfolio_imported_2026-01-22_11-20-30.json
    └── ... (more timestamped backups)

admin/
├── manage-backups.php                   (Management UI)
├── api/
│   └── backups.php                      (API endpoint)
├── css/
│   └── admin-style.css                  (Styles)
├── includes/
│   └── functions.php                    (Helper functions)
└── ... (other admin files)

helpers/
└── BackupManager.php                    (Core class)
*/

/**
 * ═══════════════════════════════════════════════════════════════
 * FEATURES SUMMARY
 * ═══════════════════════════════════════════════════════════════
 */

/*
✓ Create timestamped backups
✓ List all available backups
✓ Restore previous versions
✓ Delete old backups
✓ Export backups (download as JSON)
✓ Import backups (upload JSON)
✓ Automatic safety backups before restore
✓ Cleanup old backups (keep N most recent)
✓ View backup statistics
✓ View detailed backup information
✓ Human-readable file sizes
✓ Atomic file operations
✓ JSON validation on import
✓ Comprehensive admin UI
✓ REST API endpoints
✓ AJAX/JavaScript compatible
✓ Mobile responsive design
✓ Session-based security
✓ Flash message system
✓ Confirmation dialogs
*/

/**
 * ═══════════════════════════════════════════════════════════════
 * SECURITY NOTES
 * ═══════════════════════════════════════════════════════════════
 */

/*
- All operations require admin login (session-based)
- Filenames are sanitized with basename()
- JSON is validated before import
- Atomic file operations prevent corruption
- Confirmation dialogs prevent accidents
- Safety backups created before restore
- No direct file paths exposed in UI
*/

/**
 * ═══════════════════════════════════════════════════════════════
 * COMMON TASKS
 * ═══════════════════════════════════════════════════════════════
 */

/*
TASK: Backup before updating portfolio data
CODE:
    $backupManager = new BackupManager('data/portfolio.json');
    $backupManager->createBackup();
    $data['new_field'] = 'value';
    savePortfolioData($data);

TASK: Restore to a specific backup
CODE:
    $backupManager = new BackupManager('data/portfolio.json');
    $backupManager->restoreBackup('portfolio_2026-01-22_09-36-11.json');

TASK: Keep only 5 most recent backups
CODE:
    $backupManager = new BackupManager('data/portfolio.json');
    $deleted = $backupManager->cleanupOldBackups(5);

TASK: Get total backup size
CODE:
    $backupManager = new BackupManager('data/portfolio.json');
    $stats = $backupManager->getBackupStats();
    echo $stats['total_size_readable'];

TASK: Check when last backup was created
CODE:
    $backupManager = new BackupManager('data/portfolio.json');
    $stats = $backupManager->getBackupStats();
    echo date('Y-m-d H:i:s', $stats['newest']['created']);
*/

/**
 * ═══════════════════════════════════════════════════════════════
 * TROUBLESHOOTING
 * ═══════════════════════════════════════════════════════════════
 */

/*
Q: Import fails with "Invalid JSON"
A: Ensure the file is a valid JSON backup (exported from this system)

Q: Backup directory not found
A: Backups directory is created automatically in data/backups/

Q: Can't restore old backup
A: Check file permissions (755), ensure backup file exists

Q: Storage getting large
A: Use cleanup function to delete old backups: cleanupOldBackups(10)

Q: Lost a backup
A: You can still access it if the file exists in data/backups/

Q: Need to keep backups off-site
A: Use export feature to download backups, store them safely
*/

?>
