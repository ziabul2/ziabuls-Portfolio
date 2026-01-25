<?php
require_once __DIR__ . '/../admin/includes/functions.php';
require_once __DIR__ . '/DatabaseManager.php';

class BlogManager {
    private static $instance = null;
    private $db;

    private function __construct() {
        // Initialize DB connection if available
        try {
            $this->db = DatabaseManager::getInstance();
        } catch (Exception $e) {
            $this->db = null; // DB not available, operate in local mode
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get all posts from JSON (Primary Source)
     */
    public function getPosts($filters = []) {
        // Trigger Auto-Publish Check
        $this->checkScheduledPosts();
        
        $data = getPortfolioData();
        $posts = $data['blog_posts'] ?? [];

        // Apply filters (Simple PHP implementation mimicking SQL)
        if (!empty($filters['status'])) {
            $posts = array_filter($posts, function($p) use ($filters) {
                return isset($p['status']) && $p['status'] === $filters['status'];
            });
        }

        // Sort descending by date
        usort($posts, function($a, $b) {
            return strtotime($b['date'] ?? $b['created_at']) - strtotime($a['date'] ?? $a['created_at']);
        });

        return $posts;
    }

    /**
     * Check for scheduled posts that need publishing
     */
    private function checkScheduledPosts() {
        $data = getPortfolioData();
        $posts = $data['blog_posts'] ?? [];
        $hasUpdates = false;
        $now = time();

        foreach ($posts as &$post) {
            if (isset($post['status']) && ($post['status'] === 'scheduled' || $post['status'] === 'draft')) {
                // Determine scheduled time
                $scheduledTime = 0;
                if (!empty($post['published_at'])) {
                    $scheduledTime = strtotime($post['published_at']);
                } elseif (!empty($post['date'])) {
                    // Fallback if only date is set
                    $scheduledTime = strtotime($post['date'] . ' ' . ($post['publish_time'] ?? '00:00'));
                }

                // If scheduled time is valid and passed, publish it
                // Only if status was explicitly 'scheduled' OR if user treats future Drafts as scheduled (based on request implication)
                // User said "if schedule post appar or get schedule time make the post schedule"
                // Let's stick to status='scheduled' triggers update. 
                // BUT user might have saved as draft with future date.
                // Let's be strict: Update 'scheduled' -> 'published'
                if ($post['status'] === 'scheduled' && $scheduledTime > 0 && $scheduledTime <= $now) {
                    $post['status'] = 'published';
                    $hasUpdates = true;
                    // Fix 'date' to now? No, keep scheduled date usually.
                }
            }
        }

        if ($hasUpdates) {
            $data['blog_posts'] = $posts;
            savePortfolioData($data);
            
            // Sync updated posts to DB
            foreach ($posts as $post) {
                if ($post['status'] === 'published') {
                   $this->syncToDatabase($post);
                }
            }
        }
    }

    /**
     * Get single post by ID
     */
    public function getPost($id) {
        $posts = $this->getPosts();
        foreach ($posts as $post) {
            if ($post['id'] == $id) {
                return $post;
            }
        }
        return null;
    }

    /**
     * Create or Update Post
     */
    public function savePost($postData) {
        $data = getPortfolioData();
        if (!isset($data['blog_posts'])) {
            $data['blog_posts'] = [];
        }

        $isNew = true;
        // Check if exists
        foreach ($data['blog_posts'] as &$post) {
            if ($post['id'] == $postData['id']) {
                // Update
                $post = array_merge($post, $postData);
                $isNew = false;
                break;
            }
        }

        if ($isNew) {
            $data['blog_posts'][] = $postData;
        }

        // 1. Save locally (JSON) - REQUIRED
        if (savePortfolioData($data)) {
            // 2. Sync to DB (Optional)
            $this->syncToDatabase($postData);
            return true;
        }
        return false;
    }

    /**
     * Delete Post
     */
    public function deletePost($id) {
        $data = getPortfolioData();
        if (!isset($data['blog_posts'])) return false;

        $originalCount = count($data['blog_posts']);
        $data['blog_posts'] = array_filter($data['blog_posts'], function($post) use ($id) {
            return $post['id'] != $id;
        });

        if (count($data['blog_posts']) < $originalCount) {
            // Re-index array
            $data['blog_posts'] = array_values($data['blog_posts']);
            
            if (savePortfolioData($data)) {
                // Try DB Delete
                if ($this->db) {
                    try {
                        $this->db->deletePost($id);
                    } catch (Exception $e) {
                        // Ignore DB errors
                    }
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Sync single post to DB
     */
    public function syncToDatabase($post) {
        if (!$this->db) return false;

        // Map fields likely to differ
        // JSON: date, DB: created_at
        // JSON: content (HTML), DB: content (HTML)
        
        $dbPost = [
            'id' => $post['id'],
            'title' => $post['title'],
            'summary' => $post['summary'],
            'content' => $post['content'],
            'image' => $post['image'],
            'status' => $post['status'],
            'author_id' => 1 // Default
        ];

        try {
            // Try Insert, if duplicate ID, Update?
            // DatabaseManager::createPost doesn't handle ON DUPLICATE KEY UPDATE generally.
            // Check if exists first
            if ($this->db->getPost($post['id'])) {
                $this->db->updatePost($post['id'], $dbPost);
            } else {
                $this->db->createPost($dbPost);
            }
        } catch (Exception $e) {
            // Log but don't stop execution
            error_log("Blog Sync Error: " . $e->getMessage());
        }
    }
}
