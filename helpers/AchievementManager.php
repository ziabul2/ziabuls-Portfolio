<?php

class AchievementManager {
    private static $instance = null;
    private $filePath;

    private function __construct() {
        $this->filePath = __DIR__ . '/../data/achievements.json';
        if (!file_exists($this->filePath)) {
            $this->saveSafely([]);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get all achievements
     */
    public function getAchievements($filters = []) {
        $json = file_get_contents($this->filePath);
        $achievements = json_decode($json, true) ?: [];

        // Apply category filter if provided
        if (!empty($filters['category'])) {
            $achievements = array_filter($achievements, function($a) use ($filters) {
                return isset($a['category']) && $a['category'] === $filters['category'];
            });
        }

        // Sort dynamically newest first by completion_date
        usort($achievements, function($a, $b) {
            $dateA = strtotime($a['completion_date'] ?? $a['created_at'] ?? '0');
            $dateB = strtotime($b['completion_date'] ?? $b['created_at'] ?? '0');
            return $dateB - $dateA;
        });

        return array_values($achievements);
    }

    /**
     * Get a single achievement by ID
     */
    public function getAchievement($id) {
        $achievements = $this->getAchievements();
        foreach ($achievements as $achievement) {
            if ($achievement['id'] === $id) {
                return $achievement;
            }
        }
        return null;
    }

    /**
     * Create or Update an Achievement
     */
    public function saveAchievement($data) {
        // Validation ensures ID exists
        if (empty($data['id'])) {
            $data['id'] = uniqid('ach_');
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Ensure optional fields are handled without breaking
        if (!isset($data['database_subject'])) {
             // We just don't set it if it's not provided
        }

        $achievements = $this->getAchievements();
        $isNew = true;

        foreach ($achievements as &$achievement) {
            if ($achievement['id'] === $data['id']) {
                $achievement = array_merge($achievement, $data);
                $isNew = false;
                break;
            }
        }

        if ($isNew) {
            $achievements[] = $data;
        }

        return $this->saveSafely($achievements);
    }

    /**
     * Delete an Achievement
     */
    public function deleteAchievement($id) {
        $achievements = $this->getAchievements();
        $originalCount = count($achievements);

        $achievements = array_filter($achievements, function($achievement) use ($id) {
            return $achievement['id'] !== $id;
        });

        if (count($achievements) < $originalCount) {
            // Re-index array explicitly
            $achievements = array_values($achievements);
            return $this->saveSafely($achievements);
        }
        return false;
    }

    /**
     * Thread-safe saving to JSON file
     */
    private function saveSafely($data) {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        // Use LOCK_EX to prevent concurrent write corruption
        $result = file_put_contents($this->filePath, $json, LOCK_EX);
        return $result !== false;
    }
}
?>
