<?php

class BackupManager {
    private $filePath;
    private $backupDir;

    public function __construct($filePath) {
        $this->filePath = $filePath;
        $this->backupDir = dirname($filePath) . '/backups/';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Creates a timestamped backup of the current file.
     * @return string|false The path to the backup file or false on failure.
     */
    public function createBackup() {
        if (!file_exists($this->filePath)) {
            return false;
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $this->backupDir . 'portfolio_' . $timestamp . '.json';
        
        if (copy($this->filePath, $backupFile)) {
            return $backupFile;
        }
        
        return false;
    }

    /**
     * Saves data atomically with automatic backup of previous version.
     * @param array $data The data to save.
     * @return bool True on success, false on failure.
     */
    public function saveSafely($data) {
        // 1. Create backup of existing data
        if (file_exists($this->filePath)) {
            $this->createBackup();
        }

        // 2. Prepare JSON content
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false; // JSON encoding failed
        }

        // 3. Write to a temporary file first
        $tempFile = $this->filePath . '.tmp';
        if (file_put_contents($tempFile, $json) === false) {
            return false; // Write failed
        }

        // 4. Verify the temporary file was written correctly (basic check)
        if (filesize($tempFile) !== strlen($json)) {
            unlink($tempFile);
            return false; // Write incomplete
        }

        // 5. Atomic rename
        // On Windows, rename might fail if target exists and is locked, but generally it's safe for replacement
        // Using rename() is atomic on POSIX, and generally safe enough here.
        // To be extra safe on Windows, we could unlink target first, but that loses atomicity.
        // PHP's rename usually overwrites on Windows too, unless locked.
        if (rename($tempFile, $this->filePath)) {
            return true;
        } else {
            // Try to cleanup temp file if rename failed
            if (file_exists($tempFile)) unlink($tempFile);
            return false;
        }
    }

    /**
     * Returns a list of available backups.
     * @return array List of backup files sorted by date (newest first).
     */
    public function getBackups() {
        $backups = [];
        $files = glob($this->backupDir . 'portfolio_*.json');
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'created' => filemtime($file),
                'size' => filesize($file)
            ];
        }

        // Sort by created date descending
        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });

        return $backups;
    }

    /**
     * Restores a specific backup file.
     * @param string $filename The basename of the backup file.
     * @return bool
     */
    public function restoreBackup($filename) {
        $backupPath = $this->backupDir . basename($filename);
        
        if (!file_exists($backupPath)) {
            return false;
        }

        // Create a safety backup of current state before restoring
        $this->createBackup();

        // Copy backup to main file
        return copy($backupPath, $this->filePath);
    }

    /**
     * Deletes a specific backup file.
     * @param string $filename The basename of the backup file.
     * @return bool True on success, false on failure.
     */
    public function deleteBackup($filename) {
        $backupPath = $this->backupDir . basename($filename);
        
        if (!file_exists($backupPath)) {
            return false;
        }

        return unlink($backupPath);
    }

    /**
     * Exports a backup file for download.
     * @param string $filename The basename of the backup file.
     * @return array|false Array with 'path' and 'filename' on success, false on failure.
     */
    public function exportBackup($filename) {
        $backupPath = $this->backupDir . basename($filename);
        
        if (!file_exists($backupPath)) {
            return false;
        }

        return [
            'path' => $backupPath,
            'filename' => basename($filename)
        ];
    }

    /**
     * Imports a backup file from an uploaded file.
     * @param array $uploadedFile The $_FILES array element.
     * @return string|false The backup filename on success, false on failure.
     */
    public function importBackup($uploadedFile) {
        if (!isset($uploadedFile) || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Validate JSON format
        $content = file_get_contents($uploadedFile['tmp_name']);
        if (json_decode($content, true) === null) {
            return false; // Invalid JSON
        }

        // Generate a timestamped filename
        $timestamp = date('Y-m-d_H-i-s');
        $filename = 'portfolio_imported_' . $timestamp . '.json';
        $backupPath = $this->backupDir . $filename;

        if (move_uploaded_file($uploadedFile['tmp_name'], $backupPath)) {
            return $filename;
        }

        return false;
    }

    /**
     * Gets statistics about backups.
     * @return array Statistics including count, total size, oldest, newest.
     */
    public function getBackupStats() {
        $backups = $this->getBackups();
        
        $totalSize = 0;
        $oldest = null;
        $newest = null;

        foreach ($backups as $backup) {
            $totalSize += $backup['size'];
            if ($oldest === null) {
                $oldest = $backup;
            }
            if ($newest === null) {
                $newest = $backup;
            }
        }

        return [
            'count' => count($backups),
            'total_size' => $totalSize,
            'total_size_readable' => $this->formatBytes($totalSize),
            'oldest' => $oldest,
            'newest' => $newest
        ];
    }

    /**
     * Formats bytes into human-readable format.
     * @param int $bytes The number of bytes.
     * @return string Formatted size string.
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Gets detailed information about a specific backup.
     * @param string $filename The basename of the backup file.
     * @return array|false Backup details or false if not found.
     */
    public function getBackupDetails($filename) {
        $backups = $this->getBackups();
        foreach ($backups as $backup) {
            if ($backup['filename'] === $filename) {
                $backup['size_readable'] = $this->formatBytes($backup['size']);
                $backup['created_readable'] = date('Y-m-d H:i:s', $backup['created']);
                return $backup;
            }
        }
        return false;
    }

    /**
     * Creates an AUTOMATIC timestamped backup.
     * Use this for system-triggered backups (e.g. before save).
     * @return string|false
     */
    public function createAutoBackup() {
        if (!file_exists($this->filePath)) return false;
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $this->backupDir . 'auto_portfolio_' . $timestamp . '.json';
        
        if (copy($this->filePath, $backupFile)) {
            return $backupFile;
        }
        return false;
    }

    /**
     * Cleans up old backups, keeping only the latest N backups.
     * Differentiates between Manual and Auto if needed, but for now cleans globally by date.
     * @param int $keepCount Number of backups to keep.
     * @return int Number of backups deleted.
     */
    public function cleanupOldBackups($keepCount = 10) {
        $backups = $this->getBackups(); // Sorted new -> old
        $deleted = 0;

        if (count($backups) > $keepCount) {
             // Keep the first $keepCount, delete the rest
            $toDelete = array_slice($backups, $keepCount);
            foreach ($toDelete as $backup) {
                if ($this->deleteBackup($backup['filename'])) {
                    $deleted++;
                }
            }
        }
        return $deleted;
    }

    /**
     * Get System Health Info
     */
    /**
     * Exports ALL backups as a ZIP file.
     * @return string|false Path to ZIP file.
     */
    public function exportAllBackups() {
        if (!class_exists('ZipArchive')) return false;

        $zip = new ZipArchive();
        $timestamp = date('Y-m-d_H-i-s');
        $zipName = $this->backupDir . 'full_backup_' . $timestamp . '.zip';

        if ($zip->open($zipName, ZipArchive::CREATE) !== TRUE) {
            return false;
        }

        $files = glob($this->backupDir . 'portfolio_*.json');
        // Include auto backups too if desired, usually 'auto_'
        $autoFiles = glob($this->backupDir . 'auto_portfolio_*.json');
        $allFiles = array_merge($files, $autoFiles);

        if (empty($allFiles)) {
             $zip->close();
             return false;
        }

        foreach ($allFiles as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();
        
        return file_exists($zipName) ? $zipName : false;
    }
}
?>
