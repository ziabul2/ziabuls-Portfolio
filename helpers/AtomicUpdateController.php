<?php
/**
 * Atomic Portfolio Update Controller
 * Handles all data saves with validation, backup, and verification
 * 
 * Usage:
 *   $updater = new AtomicUpdateController();
 *   $result = $updater->update('hero', $newData);
 *   
 * Returns:
 *   ['success' => true/false, 'message' => '...', 'backup' => '...', 'error' => '...']
 */

require_once __DIR__ . '/../helpers/BackupManager.php';

class AtomicUpdateController {
    private $portfolioPath;
    private $backupManager;
    private $errors = [];
    
    public function __construct() {
        $this->portfolioPath = __DIR__ . '/../data/portfolio.json';
        $this->backupManager = new BackupManager($this->portfolioPath);
    }
    
    /**
     * Update a specific section of the portfolio
     * 
     * @param string $section Section name (hero, skills, projects, etc.)
     * @param array $data New data for this section
     * @return array Result with success, message, backup, and error info
     */
    public function update($section, $data) {
        // Phase 1: Validate input parameters
        if (!is_string($section) || empty($section)) {
            return $this->fail('Invalid section name');
        }
        
        if (!is_array($data) || empty($data)) {
            return $this->fail('Invalid data format');
        }
        
        // Phase 2: Load current data
        $current = $this->loadData();
        if ($current === false) {
            return $this->fail('Failed to load current portfolio data');
        }
        
        // Phase 3: Check section exists
        if (!isset($current[$section])) {
            return $this->fail("Section '{$section}' does not exist");
        }
        
        // Phase 4: Validate new data structure
        if (!$this->validateDataStructure($section, $data)) {
            return $this->fail('Data validation failed: ' . implode(', ', $this->errors));
        }
        
        // Phase 5: Create backup of current state
        $backup = $this->backupManager->createBackup();
        if (!$backup) {
            return $this->fail('Failed to create backup');
        }
        
        // Phase 6: Merge data and prepare for write
        $current[$section] = $data;
        
        // Phase 7: Perform atomic save
        if (!$this->backupManager->saveSafely($current)) {
            return $this->fail('Failed to save changes to portfolio');
        }
        
        // Phase 8: Verify write was successful
        $verify = $this->loadData();
        if ($verify === false || !isset($verify[$section])) {
            return $this->fail('Verification failed: data not properly saved');
        }
        
        // Phase 9: Deep verify the section matches
        if (!$this->deepVerify($verify[$section], $data)) {
            return $this->fail('Verification failed: saved data does not match input');
        }
        
        // Phase 10: Log the update
        $this->logUpdate($section, $backup);
        
        // All success!
        return [
            'success' => true,
            'message' => ucfirst($section) . ' section updated successfully',
            'section' => $section,
            'backup_file' => basename($backup),
            'backup_path' => $backup,
            'timestamp' => time()
        ];
    }
    
    /**
     * Batch update multiple sections atomically
     * Either all sections update or none update
     * 
     * @param array $updates Array of ['section' => [...], 'section2' => [...]]
     * @return array Result
     */
    public function batchUpdate($updates) {
        if (!is_array($updates) || empty($updates)) {
            return $this->fail('Invalid batch update format');
        }
        
        $current = $this->loadData();
        if ($current === false) {
            return $this->fail('Failed to load current portfolio data');
        }
        
        // Validate all sections first
        foreach ($updates as $section => $data) {
            if (!isset($current[$section])) {
                return $this->fail("Section '{$section}' does not exist");
            }
            if (!$this->validateDataStructure($section, $data)) {
                return $this->fail("Validation failed for '{$section}': " . implode(', ', $this->errors));
            }
        }
        
        // Create backup before any changes
        $backup = $this->backupManager->createBackup();
        if (!$backup) {
            return $this->fail('Failed to create backup');
        }
        
        // Apply all changes
        foreach ($updates as $section => $data) {
            $current[$section] = $data;
        }
        
        // Save atomically
        if (!$this->backupManager->saveSafely($current)) {
            return $this->fail('Failed to save batch changes');
        }
        
        // Verify
        $verify = $this->loadData();
        if ($verify === false) {
            return $this->fail('Verification failed');
        }
        
        $this->logUpdate('batch_' . implode('_', array_keys($updates)), $backup);
        
        return [
            'success' => true,
            'message' => 'Batch update successful (' . count($updates) . ' sections updated)',
            'sections_updated' => count($updates),
            'backup_file' => basename($backup),
            'timestamp' => time()
        ];
    }
    
    /**
     * Validate data structure matches expected format
     * 
     * @param string $section Section name
     * @param array $data Data to validate
     * @return bool
     */
    private function validateDataStructure($section, $data) {
        $this->errors = [];
        
        // Basic checks
        if (!is_array($data)) {
            $this->errors[] = 'Data must be an array';
            return false;
        }
        
        // Section-specific validation
        switch ($section) {
            case 'hero':
                return $this->validateHeroSection($data);
            case 'about_section':
                return $this->validateAboutSection($data);
            case 'skills':
                return $this->validateSkillsSection($data);
            case 'projects_section':
                return $this->validateProjectsSection($data);
            case 'blog_posts':
                return $this->validateBlogSection($data);
            case 'contact':
                return $this->validateContactSection($data);
            case 'seo':
                return $this->validateSeoSection($data);
            case 'social_links':
                return $this->validateSocialLinks($data);
            default:
                // For unknown sections, just ensure it's an array
                return !empty($data);
        }
    }
    
    /**
     * Validate hero section
     */
    private function validateHeroSection($data) {
        $required = ['name', 'roles', 'description', 'image'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->errors[] = "Hero section missing required field: {$field}";
                return false;
            }
        }
        
        // Validate image path
        if (!$this->isValidAssetPath($data['image'])) {
            $this->errors[] = 'Hero image must be in assets folder';
            return false;
        }
        
        // Validate roles is array
        if (!is_array($data['roles']) || empty($data['roles'])) {
            $this->errors[] = 'Hero roles must be a non-empty array';
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate about section
     */
    private function validateAboutSection($data) {
        if (!isset($data['image']) || !$this->isValidAssetPath($data['image'])) {
            $this->errors[] = 'About image must be in assets folder';
            return false;
        }
        
        if (!isset($data['intro']) || !is_string($data['intro'])) {
            $this->errors[] = 'About intro must be a string';
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate skills section
     */
    private function validateSkillsSection($data) {
        if (!isset($data['items']) || !is_array($data['items'])) {
            $this->errors[] = 'Skills items must be an array';
            return false;
        }
        
        foreach ($data['items'] as $idx => $skill) {
            if (!isset($skill['category']) || !isset($skill['skills'])) {
                $this->errors[] = "Skill item {$idx} missing category or skills";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate projects section
     */
    private function validateProjectsSection($data) {
        if (!isset($data['items']) || !is_array($data['items'])) {
            $this->errors[] = 'Projects items must be an array';
            return false;
        }
        
        foreach ($data['items'] as $idx => $project) {
            if (!isset($project['title']) || empty($project['title'])) {
                $this->errors[] = "Project {$idx} missing title";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate blog section
     */
    private function validateBlogSection($data) {
        if (!is_array($data)) {
            $this->errors[] = 'Blog posts must be an array';
            return false;
        }
        
        foreach ($data as $idx => $post) {
            if (!isset($post['id']) || !isset($post['title'])) {
                $this->errors[] = "Blog post {$idx} missing id or title";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate contact section
     */
    private function validateContactSection($data) {
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Contact email is invalid';
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate SEO section
     */
    private function validateSeoSection($data) {
        if (isset($data['favicon']) && !empty($data['favicon'])) {
            if (!$this->isValidAssetPath($data['favicon'])) {
                $this->errors[] = 'SEO favicon must be in assets folder';
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate social links
     */
    private function validateSocialLinks($data) {
        if (!is_array($data)) {
            $this->errors[] = 'Social links must be an array';
            return false;
        }
        
        foreach ($data as $idx => $link) {
            if (!isset($link['platform']) || !isset($link['url'])) {
                $this->errors[] = "Social link {$idx} missing platform or url";
                return false;
            }
            
            if (!filter_var($link['url'], FILTER_VALIDATE_URL)) {
                $this->errors[] = "Social link {$idx} has invalid URL";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if path is a valid asset path
     */
    private function isValidAssetPath($path) {
        // Must not contain directory traversal
        if (strpos($path, '..') !== false) {
            return false;
        }
        
        // Should be in assets folder
        if (strpos($path, 'assets/') !== 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Deep verify that saved data matches expected
     */
    private function deepVerify($saved, $expected) {
        // Simple equality check for arrays
        return json_encode($saved) === json_encode($expected);
    }
    
    /**
     * Load data from portfolio.json
     */
    private function loadData() {
        if (!file_exists($this->portfolioPath)) {
            return false;
        }
        
        $json = file_get_contents($this->portfolioPath);
        $data = json_decode($json, true);
        
        return is_array($data) ? $data : false;
    }
    
    /**
     * Log the update
     */
    private function logUpdate($section, $backup) {
        // Log to a simple text file for audit trail
        $log_file = __DIR__ . '/../data/update_log.txt';
        
        $entry = json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'section' => $section,
            'backup_file' => basename($backup),
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100)
        ]) . "\n";
        
        @file_put_contents($log_file, $entry, FILE_APPEND);
    }
    
    /**
     * Return success response
     */
    private function success($data = []) {
        return array_merge(['success' => true], $data);
    }
    
    /**
     * Return failure response
     */
    private function fail($message) {
        return [
            'success' => false,
            'error' => $message,
            'timestamp' => time()
        ];
    }
}
?>
