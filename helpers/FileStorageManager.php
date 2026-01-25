<?php
/**
 * FileStorageManager - Manages static content with file-based storage
 * Integrates with BackupManager for automatic backups and rollback
 */

require_once __DIR__ . '/BackupManager.php';

class FileStorageManager {
    private $filePath;
    private $backupManager;
    private $data;

    /**
     * Constructor
     * @param string $filePath Path to the JSON data file
     */
    public function __construct($filePath) {
        $this->filePath = $filePath;
        $this->backupManager = new BackupManager($filePath);
        $this->loadData();
    }

    /**
     * Load data from file
     */
    private function loadData() {
        if (!file_exists($this->filePath)) {
            $this->data = [];
            return;
        }

        $json = file_get_contents($this->filePath);
        $this->data = json_decode($json, true) ?: [];
    }

    /**
     * Get all data
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Get specific section
     * @param string $section Section name (e.g., 'hero', 'skills_section', 'profile')
     */
    public function getSection($section) {
        return $this->data[$section] ?? null;
    }

    /**
     * Update specific section
     * @param string $section Section name
     * @param mixed $value New value for the section
     */
    public function updateSection($section, $value) {
        $this->data[$section] = $value;
        return $this->save();
    }

    /**
     * Update multiple sections at once
     * @param array $sections Associative array of section => value
     */
    public function updateSections($sections) {
        foreach ($sections as $section => $value) {
            $this->data[$section] = $value;
        }
        return $this->save();
    }

    /**
     * Save data to file with automatic backup
     */
    public function save() {
        return $this->backupManager->saveSafely($this->data);
    }

    /**
     * Get list of available backups
     */
    public function getBackups() {
        return $this->backupManager->getBackups();
    }

    /**
     * Restore from a backup
     * @param string $filename Backup filename
     */
    public function restore($filename) {
        $result = $this->backupManager->restoreBackup($filename);
        if ($result) {
            $this->loadData(); // Reload data after restore
        }
        return $result;
    }

    /**
     * Create manual backup (returns backup filename)
     */
    public function createBackup() {
        return $this->backupManager->createBackup();
    }

    /**
     * Delete a backup
     * @param string $filename Backup filename
     */
    public function deleteBackup($filename) {
        return $this->backupManager->deleteBackup($filename);
    }

    /**
     * Get backup statistics
     */
    public function getBackupStats() {
        return $this->backupManager->getBackupStats();
    }

    /**
     * Validate section data before saving
     * @param string $section Section name
     * @param mixed $data Data to validate
     */
    public function validateSection($section, $data) {
        // Basic validation - can be extended with schema validation
        switch ($section) {
            case 'hero':
                return isset($data['name']) && isset($data['roles']);
            
            case 'skills_section':
                return isset($data['title']) && isset($data['categories']);
            
            case 'projects_section':
                return isset($data['title']) && isset($data['items']);
            
            case 'contact_section':
                return isset($data['title']);
            
            case 'about_section':
                return isset($data['title']) && isset($data['intro']);
            
            case 'seo':
                return isset($data['title']);
            
            case 'profile':
                return true; // Profile can have any structure
            
            case 'social_links':
                return is_array($data);
            
            case 'footer':
                return isset($data['logo_text']);
            
            case 'blog_section':
                return isset($data['title']);
            
            default:
                return true; // Allow unknown sections
        }
    }

    /**
     * Update section with validation
     * @param string $section Section name
     * @param mixed $value New value
     */
    public function updateSectionSafely($section, $value) {
        if (!$this->validateSection($section, $value)) {
            return false;
        }
        
        return $this->updateSection($section, $value);
    }

    /**
     * Export all static sections (excluding dynamic content like blog_posts)
     */
    public function exportStaticSections() {
        $staticSections = [
            'seo',
            'social_links',
            'hero',
            'projects_section',
            'skills_section',
            'about_section',
            'contact_section',
            'footer',
            'profile',
            'blog_section'
        ];

        $export = [];
        foreach ($staticSections as $section) {
            if (isset($this->data[$section])) {
                $export[$section] = $this->data[$section];
            }
        }

        return $export;
    }

    /**
     * Import static sections from array
     */
    public function importStaticSections($sections) {
        foreach ($sections as $section => $value) {
            if ($this->validateSection($section, $value)) {
                $this->data[$section] = $value;
            }
        }
        
        return $this->save();
    }

    /**
     * Cleanup old backups, keeping only the latest N backups
     * @param int $keepCount Number of backups to keep
     */
    public function cleanupBackups($keepCount = 10) {
        return $this->backupManager->cleanupOldBackups($keepCount);
    }
}
