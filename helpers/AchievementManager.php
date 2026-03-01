<?php
/**
 * AchievementManager configures saving achievements dynamically securely.
 */
class AchievementManager {
    private $dataFile;
    private $lockFile;

    public function __construct() {
        $this->dataFile = __DIR__ . '/../data/achievements.json';
        $this->lockFile = __DIR__ . '/../data/achievements.lock';
        
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    private function lock() {
        $fp = fopen($this->lockFile, 'c');
        if (flock($fp, LOCK_EX)) {
            return $fp;
        }
        fclose($fp);
        return false;
    }

    private function unlock($fp) {
        if ($fp) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function getAchievements($category = null) {
        $data = json_decode(file_get_contents($this->dataFile), true) ?? [];
        
        if ($category) {
            $data = array_filter($data, function($item) use ($category) {
                return isset($item['category']) && $item['category'] === $category;
            });
        }
        
        // Sort newest first
        usort($data, function($a, $b) {
            return strtotime($b['completion_date']) - strtotime($a['completion_date']);
        });
        
        return array_values($data);
    }

    public function getAchievement($id) {
        $data = $this->getAchievements();
        foreach ($data as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    public function saveSafely($dataArray) {
        $fp = $this->lock();
        if ($fp) {
            $success = file_put_contents($this->dataFile, json_encode($dataArray, JSON_PRETTY_PRINT));
            $this->unlock($fp);
            return $success !== false;
        }
        return false;
    }

    public function saveAchievement($postData) {
        $data = $this->getAchievements();
        $isNew = true;
        
        $postData['updated_at'] = time();

        // Optional specific fields handling (backward compatibility)
        if (!isset($postData['database_subject']) && array_key_exists('database_subject', $postData) && empty($postData['database_subject'])) {
             // Don't add it or let it remain empty
        }

        foreach ($data as &$item) {
            if ($item['id'] === $postData['id']) {
                $item = array_merge($item, $postData);
                $isNew = false;
                break;
            }
        }

        if ($isNew) {
            $postData['created_at'] = time();
            $data[] = $postData;
        }

        return $this->saveSafely($data);
    }

    public function deleteAchievement($id) {
        $data = $this->getAchievements();
        $originalCount = count($data);
        
        $data = array_filter($data, function($item) use ($id) {
            return $item['id'] !== $id;
        });

        if (count($data) < $originalCount) {
            return $this->saveSafely(array_values($data));
        }
        return false;
    }
}
