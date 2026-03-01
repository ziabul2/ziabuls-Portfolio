<?php
/**
 * DatabaseManager - Handles all database operations for dynamic content
 * Uses Singleton pattern for connection management
 */

class DatabaseManager {
    private static $instance = null;
    private $connection = null;
    private $config = null;

    /**
     * Private constructor for Singleton pattern
     */
    private function __construct() {
        $this->config = require __DIR__ . '/../config/database.php';
        $this->connect();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Connect to database
     */
    private function connect() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['database'],
                $this->config['charset']
            );

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $stmt = $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // ==================== BLOG POST OPERATIONS ====================

    /**
     * Create a new blog post
     */
    public function createPost($data) {
        try {
            $sql = "INSERT INTO blog_posts (id, title, summary, content, image, status, author_id) 
                    VALUES (:id, :title, :summary, :content, :image, :status, :author_id)";
            
            $stmt = $this->connection->prepare($sql);
            
            return $stmt->execute([
                ':id' => $data['id'],
                ':title' => $data['title'],
                ':summary' => $data['summary'] ?? '',
                ':content' => $data['content'] ?? '',
                ':image' => $data['image'] ?? '',
                ':status' => $data['status'] ?? 'draft',
                ':author_id' => $data['author_id'] ?? 1
            ]);
        } catch (PDOException $e) {
            error_log('Create post failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a single blog post by ID
     */
    public function getPost($id) {
        try {
            $sql = "SELECT * FROM blog_posts WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Get post failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all blog posts with optional filters
     */
    public function getPosts($filters = []) {
        try {
            $sql = "SELECT * FROM blog_posts WHERE 1=1";
            $params = [];

            // Filter by status
            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            // Search by title
            if (isset($filters['search'])) {
                $sql .= " AND (title LIKE :search OR summary LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            // Order by
            $orderBy = $filters['order_by'] ?? 'created_at';
            $orderDir = $filters['order_dir'] ?? 'DESC';
            $sql .= " ORDER BY {$orderBy} {$orderDir}";

            // Pagination
            if (isset($filters['limit'])) {
                $sql .= " LIMIT :limit";
                $offset = $filters['offset'] ?? 0;
                if ($offset > 0) {
                    $sql .= " OFFSET :offset";
                }
            }

            $stmt = $this->connection->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if (isset($filters['limit'])) {
                $stmt->bindValue(':limit', (int)$filters['limit'], PDO::PARAM_INT);
                if (isset($filters['offset']) && $filters['offset'] > 0) {
                    $stmt->bindValue(':offset', (int)$filters['offset'], PDO::PARAM_INT);
                }
            }

            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Get posts failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update a blog post
     */
    public function updatePost($id, $data) {
        try {
            $sql = "UPDATE blog_posts 
                    SET title = :title,
                        summary = :summary,
                        content = :content,
                        image = :image,
                        status = :status
                    WHERE id = :id";
            
            $stmt = $this->connection->prepare($sql);
            
            return $stmt->execute([
                ':id' => $id,
                ':title' => $data['title'],
                ':summary' => $data['summary'] ?? '',
                ':content' => $data['content'] ?? '',
                ':image' => $data['image'] ?? '',
                ':status' => $data['status'] ?? 'draft'
            ]);
        } catch (PDOException $e) {
            error_log('Update post failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a blog post
     */
    public function deletePost($id) {
        try {
            $sql = "DELETE FROM blog_posts WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Delete post failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Count total posts with filters
     */
    public function countPosts($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM blog_posts WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            if (isset($filters['search'])) {
                $sql .= " AND (title LIKE :search OR summary LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['total'];
        } catch (PDOException $e) {
            error_log('Count posts failed: ' . $e->getMessage());
            return 0;
        }
    }

    // ==================== MEDIA LIBRARY OPERATIONS ====================

    /**
     * Add media to library
     */
    public function addMedia($data) {
        try {
            $sql = "INSERT INTO media_library (filename, filepath, filetype, filesize, alt_text, used_in) 
                    VALUES (:filename, :filepath, :filetype, :filesize, :alt_text, :used_in)";
            
            $stmt = $this->connection->prepare($sql);
            
            $result = $stmt->execute([
                ':filename' => $data['filename'],
                ':filepath' => $data['filepath'],
                ':filetype' => $data['filetype'] ?? '',
                ':filesize' => $data['filesize'] ?? 0,
                ':alt_text' => $data['alt_text'] ?? '',
                ':used_in' => $data['used_in'] ?? '[]'
            ]);

            if ($result) {
                return $this->connection->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log('Add media failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get media by ID
     */
    public function getMedia($id) {
        try {
            $sql = "SELECT * FROM media_library WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Get media failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all media files
     */
    public function getAllMedia() {
        try {
            $sql = "SELECT * FROM media_library ORDER BY uploaded_at DESC";
            $stmt = $this->connection->query($sql);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Get all media failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete media
     */
    public function deleteMedia($id) {
        try {
            $sql = "DELETE FROM media_library WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Delete media failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update media usage references
     */
    public function updateMediaUsage($id, $usedIn) {
        try {
            $sql = "UPDATE media_library SET used_in = :used_in WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            
            return $stmt->execute([
                ':id' => $id,
                ':used_in' => json_encode($usedIn)
            ]);
        } catch (PDOException $e) {
            error_log('Update media usage failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute raw SQL (for migrations and setup)
     */
    public function executeSQL($sql) {
        try {
            return $this->connection->exec($sql);
        } catch (PDOException $e) {
            error_log('Execute SQL failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
