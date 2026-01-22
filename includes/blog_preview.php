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
                $published_posts = array_filter($data['blog_posts'], function($post) {
                    return isset($post['status']) && $post['status'] === 'published';
                });
                // Sort by date descending
                usort($published_posts, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
                // Take top 3
                $latest_posts = array_slice($published_posts, 0, 3);
                
                if (empty($latest_posts)): ?>
                    <p style="color: #666; font-style: italic;">No blog posts available yet.</p>
                <?php else: ?>
                    <?php foreach ($latest_posts as $post): ?>
                    <!-- Blog Card -->
                    <div class="project-card">
                        <div class="project-img">
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                        </div>
                        <div class="project-tech"><?php echo htmlspecialchars($post['date']); ?></div>
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
