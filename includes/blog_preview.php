    <!-- Blog Section -->
    <section id="blog" class="projects">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span><?php echo $data['blog_section']['title']; ?></h2>
                <div class="section-line"></div>
                <a href="<?php echo $data['blog_section']['view_all_link']; ?>" class="btn btn-sm"><?php echo $data['blog_section']['view_all_text']; ?></a>
            </div>
            
            <div class="project-grid">
                <?php 
                // Fetch latest published posts (Hybrid: JSON Primary)
                try {
                    require_once __DIR__ . '/../helpers/BlogManager.php';
                    $blogManager = BlogManager::getInstance();
                    $all_posts = $blogManager->getPosts(['status' => 'published']);
                    
                    // Slice first 3
                    $latest_posts = array_slice($all_posts, 0, 3);
                } catch (Exception $e) {
                    error_log('Error fetching blog posts for homepage: ' . $e->getMessage());
                    $latest_posts = [];
                }
                
                if (empty($latest_posts)): ?>
                    <p style="color: #666; font-style: italic;">No blog posts available yet.</p>
                <?php else: ?>
                    <?php foreach ($latest_posts as $post): ?>
                    <!-- Blog Card -->
                    <div class="project-card">
                        <div class="project-img">
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                        </div>
                        <div class="project-tech">
                            <?php 
                            $dateStr = $post['published_at'] ?? ($post['date'] . ' ' . ($post['publish_time'] ?? '')) ?? $post['created_at'] ?? 'now';
                            echo date('M d, Y \a\t g:i A', strtotime($dateStr)); 
                            ?>
                        </div>
                        <div class="project-content">
                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p><?php echo htmlspecialchars($post['summary']); ?></p>
                            <a href="post.php?id=<?php echo urlencode($post['id']); ?>" class="btn btn-sm">Read More</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
