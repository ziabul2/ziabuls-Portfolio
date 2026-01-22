<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

// Handle actions
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$filename = $_GET['filename'] ?? $_POST['filename'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'restore' && $filename) {
        if (restoreBackup($filename)) {
            setFlashMessage('Backup restored successfully!', 'success');
        } else {
            setFlashMessage('Failed to restore backup.', 'error');
        }
        header('Location: manage-backups.php');
        exit;
    } elseif ($action === 'delete' && $filename) {
        $filePath = getPortfolioFilePath();
        $backupManager = new BackupManager($filePath);
        if ($backupManager->deleteBackup($filename)) {
            setFlashMessage('Backup deleted successfully!', 'success');
        } else {
            setFlashMessage('Failed to delete backup.', 'error');
        }
        header('Location: manage-backups.php');
        exit;
    } elseif ($action === 'import' && isset($_FILES['backup_file'])) {
        $filePath = getPortfolioFilePath();
        $backupManager = new BackupManager($filePath);
        
        if ($result = $backupManager->importBackup($_FILES['backup_file'])) {
            setFlashMessage('Backup imported successfully! File: ' . $result, 'success');
        } else {
            setFlashMessage('Failed to import backup. Please ensure the file is a valid JSON.', 'error');
        }
        header('Location: manage-backups.php');
        exit;
    } elseif ($action === 'cleanup') {
        $keepCount = (int)($_POST['keep_count'] ?? 10);
        $filePath = getPortfolioFilePath();
        $backupManager = new BackupManager($filePath);
        $deleted = $backupManager->cleanupOldBackups($keepCount);
        setFlashMessage("Cleanup complete! Deleted $deleted old backup(s).", 'success');
        header('Location: manage-backups.php');
        exit;
    }
}

// Get flash message
$flash = getFlashMessage();

// Get backups list and stats
$filePath = getPortfolioFilePath();
$backupManager = new BackupManager($filePath);
$backups = $backupManager->getBackups();
$stats = $backupManager->getBackupStats();
?>

<div class="backup-header" style="margin-bottom: 40px;">
    <h1><i class="fas fa-save"></i> Backup Management</h1>
    <p>Manage your portfolio data backups, restore previous versions, and import/export backups.</p>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type'] === 'success' ? 'success-msg' : 'error-msg'; ?>" style="margin-bottom: 20px;">
        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="backup-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px;">
    <div class="stat-card" style="border-left: 5px solid var(--success-color);">
        <h4><i class="fas fa-database"></i> Total Backups</h4>
        <p style="font-size: 28px; color: var(--success-color); font-weight: bold; margin: 10px 0;">
            <?php echo $stats['count']; ?>
        </p>
        <small>Backup(s) available</small>
    </div>

    <div class="stat-card" style="border-left: 5px solid var(--accent-color);">
        <h4><i class="fas fa-hdd"></i> Total Size</h4>
        <p style="font-size: 28px; color: var(--accent-color); font-weight: bold; margin: 10px 0;">
            <?php echo $stats['total_size_readable']; ?>
        </p>
        <small>Combined size of all backups</small>
    </div>

    <div class="stat-card" style="border-left: 5px solid #61afef;">
        <h4><i class="fas fa-file-import"></i> Latest Backup</h4>
        <p style="font-size: 16px; color: #61afef; font-weight: bold; margin: 10px 0;">
            <?php if ($stats['newest']): ?>
                <small><?php echo date('M d, Y H:i', $stats['newest']['created']); ?></small>
            <?php else: ?>
                <small>No backups</small>
            <?php endif; ?>
        </p>
        <small>Most recent backup</small>
    </div>

    <div class="stat-card" style="border-left: 5px solid #98c379;">
        <h4><i class="fas fa-history"></i> Oldest Backup</h4>
        <p style="font-size: 16px; color: #98c379; font-weight: bold; margin: 10px 0;">
            <?php if ($stats['oldest']): ?>
                <small><?php echo date('M d, Y H:i', $stats['oldest']['created']); ?></small>
            <?php else: ?>
                <small>N/A</small>
            <?php endif; ?>
        </p>
        <small>First backup on record</small>
    </div>
</div>

<!-- Action Buttons -->
<div class="action-buttons" style="display: flex; gap: 10px; margin-bottom: 40px; flex-wrap: wrap;">
    <button onclick="document.getElementById('importModal').style.display='block'" class="btn-add" style="background: var(--accent-color);">
        <i class="fas fa-file-import"></i> Import Backup
    </button>
    
    <button onclick="document.getElementById('createBackupForm').submit()" class="btn-add" style="background: var(--success-color);">
        <i class="fas fa-plus"></i> Create New Backup
    </button>

    <button onclick="document.getElementById('cleanupModal').style.display='block'" class="btn-add" style="background: #61afef;">
        <i class="fas fa-broom"></i> Cleanup Old Backups
    </button>
</div>

<!-- Create Backup Form (Hidden) -->
<form id="createBackupForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="create_backup">
</form>

<!-- Handle Create Backup -->
<?php
if ($action === 'create_backup') {
    $filePath = getPortfolioFilePath();
    $backupManager = new BackupManager($filePath);
    if ($backupManager->createBackup()) {
        setFlashMessage('New backup created successfully!', 'success');
    } else {
        setFlashMessage('Failed to create backup.', 'error');
    }
    header('Location: manage-backups.php');
    exit;
}
?>

<!-- Backups Table -->
<div class="editor-card">
    <h3 style="margin-bottom: 20px;"><i class="fas fa-list"></i> Backup History</h3>
    
    <?php if (empty($backups)): ?>
        <p style="text-align: center; color: #999; padding: 40px;">
            <i class="fas fa-inbox"></i> No backups found. Create one to get started!
        </p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--accent-color);">
                        <th style="padding: 12px; text-align: left; color: var(--accent-color);">Filename</th>
                        <th style="padding: 12px; text-align: left; color: var(--accent-color);">Created</th>
                        <th style="padding: 12px; text-align: left; color: var(--accent-color);">Size</th>
                        <th style="padding: 12px; text-align: center; color: var(--accent-color);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr style="border-bottom: 1px solid #444; transition: 0.2s;" onmouseover="this.style.backgroundColor='rgba(198, 120, 221, 0.1)'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 12px;">
                                <code style="background: #2c3e50; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo htmlspecialchars($backup['filename']); ?>
                                </code>
                            </td>
                            <td style="padding: 12px;">
                                <small><?php echo date('Y-m-d H:i:s', $backup['created']); ?></small>
                            </td>
                            <td style="padding: 12px;">
                                <small><?php echo round($backup['size'] / 1024, 2); ?> KB</small>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                    <button type="submit" class="btn-action" style="background: var(--success-color); color: white; padding: 6px 12px; border: none; border-radius: 3px; cursor: pointer; margin: 2px; transition: 0.2s;" title="Restore this backup" onclick="return confirm('Are you sure you want to restore this backup? A safety backup of the current state will be created.');">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                </form>

                                <button onclick="showBackupDetails('<?php echo htmlspecialchars($backup['filename']); ?>')" class="btn-action" style="background: #61afef; color: white; padding: 6px 12px; border: none; border-radius: 3px; cursor: pointer; margin: 2px; transition: 0.2s;" title="View backup details">
                                    <i class="fas fa-eye"></i> View
                                </button>

                                <a href="api/backups.php?action=export&filename=<?php echo urlencode($backup['filename']); ?>" class="btn-action" style="background: #98c379; color: white; padding: 6px 12px; border: none; border-radius: 3px; cursor: pointer; margin: 2px; transition: 0.2s; display: inline-block; text-decoration: none;" title="Download backup">
                                    <i class="fas fa-download"></i> Export
                                </a>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                    <button type="submit" class="btn-action" style="background: var(--error-color); color: white; padding: 6px 12px; border: none; border-radius: 3px; cursor: pointer; margin: 2px; transition: 0.2s;" title="Delete backup" onclick="return confirm('Are you sure you want to delete this backup? This action cannot be undone.');">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Import Modal -->
<div id="importModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('importModal').style.display='none'">&times;</span>
        <h2 style="margin-bottom: 20px;"><i class="fas fa-file-import"></i> Import Backup</h2>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">
            
            <div class="form-group">
                <label for="backup_file">Select Backup File (JSON):</label>
                <input type="file" id="backup_file" name="backup_file" accept=".json" required style="padding: 10px; margin-top: 8px;">
                <small style="color: #999; display: block; margin-top: 5px;">
                    Upload a previously exported backup file (JSON format only)
                </small>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn-add" style="background: var(--accent-color); flex: 1;">
                    <i class="fas fa-upload"></i> Import Backup
                </button>
                <button type="button" onclick="document.getElementById('importModal').style.display='none'" class="btn-add" style="background: #666; flex: 1;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cleanup Modal -->
<div id="cleanupModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('cleanupModal').style.display='none'">&times;</span>
        <h2 style="margin-bottom: 20px;"><i class="fas fa-broom"></i> Cleanup Old Backups</h2>
        
        <p style="margin-bottom: 20px; color: #aaa;">
            This will keep only the most recent backups and delete older ones to save storage space.
        </p>

        <form method="POST">
            <input type="hidden" name="action" value="cleanup">
            
            <div class="form-group">
                <label for="keep_count">Number of Backups to Keep:</label>
                <input type="number" id="keep_count" name="keep_count" value="10" min="1" max="50" style="padding: 10px; margin-top: 8px; width: 100%;">
                <small style="color: #999; display: block; margin-top: 5px;">
                    Backups older than the number specified will be deleted
                </small>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn-add" style="background: var(--error-color); flex: 1;" onclick="return confirm('This will delete old backups. Are you sure?');">
                    <i class="fas fa-check"></i> Cleanup
                </button>
                <button type="button" onclick="document.getElementById('cleanupModal').style.display='none'" class="btn-add" style="background: #666; flex: 1;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Backup Details Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;"><i class="fas fa-info-circle"></i> Backup Details</h2>
            <button onclick="document.getElementById('detailsModal').style.display='none'" class="close" style="float: none; color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; background: none; border: none; padding: 0; width: auto;">
                ✕
            </button>
        </div>
        <div id="detailsContent" style="line-height: 1.8; padding: 20px; background: rgba(0,0,0,0.3); border-radius: 6px;">
            <!-- Loaded via JavaScript -->
        </div>
        <button onclick="document.getElementById('detailsModal').style.display='none'" class="btn-add" style="background: #666; margin-top: 20px; width: 100%;">
            Close
        </button>
    </div>
</div>

<!-- Download Handler -->
<?php
if ($action === 'download' && $filename) {
    $backup = $backupManager->exportBackup($filename);
    if ($backup) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
        readfile($backup['path']);
        exit;
    }
}
?>

<script>
function showBackupDetails(filename) {
    // Parse filename to extract datetime
    let cleanName = filename.replace('.json', '').replace('portfolio_', '').replace('_', ' ');
    
    // Format for readability
    let displayName = filename.replace('portfolio_', '').replace('.json', '');
    
    // Create readable date format
    const parts = displayName.match(/(\d{4})-(\d{2})-(\d{2})_(\d{2})-(\d{2})-(\d{2})/);
    let readableDate = '';
    
    if (parts) {
        const year = parts[1];
        const month = parts[2];
        const day = parts[3];
        const hour = parts[4];
        const minute = parts[5];
        const second = parts[6];
        
        const date = new Date(year, month - 1, day, hour, minute, second);
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        readableDate = date.toLocaleDateString('en-US', options);
    }
    
    // Show simple, human-readable info
    document.getElementById('detailsContent').innerHTML = `
        <div style="display: grid; grid-template-columns: 120px 1fr; gap: 15px;">
            <strong style="color: var(--accent-color);">Filename:</strong>
            <span style="word-break: break-all; font-family: 'Courier New', monospace; background: #2c3e50; padding: 8px; border-radius: 3px;">${filename}</span>
            
            <strong style="color: var(--accent-color);">Created:</strong>
            <span>${readableDate || 'Unknown'}</span>
            
            <strong style="color: var(--accent-color);">Status:</strong>
            <span><i class="fas fa-check-circle" style="color: var(--success-color);"></i> Available</span>
        </div>
    `;
    document.getElementById('detailsModal').style.display = 'block';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const importModal = document.getElementById('importModal');
    const cleanupModal = document.getElementById('cleanupModal');
    const detailsModal = document.getElementById('detailsModal');
    
    if (event.target === importModal) {
        importModal.style.display = 'none';
    }
    if (event.target === cleanupModal) {
        cleanupModal.style.display = 'none';
    }
    if (event.target === detailsModal) {
        detailsModal.style.display = 'none';
    }
};

// Enter to submit forms
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('backup_file');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (!this.value.endsWith('.json')) {
                alert('Please select a JSON file');
                this.value = '';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
