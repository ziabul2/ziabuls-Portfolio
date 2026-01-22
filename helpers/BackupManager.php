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
}
?>
