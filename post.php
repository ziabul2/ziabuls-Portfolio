<?php
/**
 * Updated post.php - Fetches individual blog post from MySQL database
 */
require_once __DIR__ . '/helpers/data_loader.php';
require_once __DIR__ . '/helpers/data_loader.php';

$data = loadPortfolioData();
$postId = $_GET['id'] ?? '';

// Fetch post from BlogManager (JSON Primary)
$post = null;
if ($postId) {
    try {
        require_once __DIR__ . '/helpers/BlogManager.php';
        $blogManager = BlogManager::getInstance();
        $post = $blogManager->getPost($postId);
        // Ensure admin settings loaded for footer fallback
        if (!isset($data['admin_settings'])) {
            $data['admin_settings'] = ['favicon' => 'assets/profile.png']; 
        }
    } catch (Exception $e) {
        error_log('Error fetching post: ' . $e->getMessage());
    }
}

// 404 if post not found or not published
if (!$post || $post['status'] !== 'published') {
    header('HTTP/1.0 404 Not Found');
    echo '404 - Post not found';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/includes/head.php'; ?>
<body style="padding-top: 100px;">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="container" style="max-width: 900px; margin: 0 auto;">
    <!-- Back Button -->
    <div style="margin-bottom: 30px;">
        <a href="blog.php" style="color: #c778dd; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-arrow-left"></i> Back to Blog
        </a>
    </div>

    <!-- Post Header -->
    <article style="margin-bottom: 50px;">
        <div style="margin-bottom: 20px;">
            <span style="color: #abb2bf; font-size: 14px;">
                <i class="fas fa-clock"></i> 
                <?php 
                $dateStr = $post['date'] ?? $post['published_at'] ?? $post['created_at'] ?? 'now';
                // Construct full timestamp if separated
                if (isset($post['publish_time']) && isset($post['date'])) {
                    $dateStr = $post['date'] . ' ' . $post['publish_time'];
                }
                echo date('F j, Y \a\t g:i A', strtotime($dateStr)); 
                ?>
            </span>
        </div>
        
        <h1 style="font-size: 42px; margin-bottom: 20px; color: #fff;">
            <?php echo htmlspecialchars($post['title']); ?>
        </h1>

        <?php if (!empty($post['summary'])): ?>
            <p style="font-size: 18px; color: #abb2bf; margin-bottom: 30px;">
                <?php echo htmlspecialchars($post['summary']); ?>
            </p>
        <?php endif; ?>

        <!-- Featured Image -->
        <?php if (!empty($post['image'])): ?>
            <div style="margin-bottom: 40px;">
                <img src="<?php echo htmlspecialchars($post['image']); ?>" 
                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                     style="width: 100%; border-radius: 8px; border: 1px solid #1e2d3d;">
            </div>
        <?php endif; ?>

        <!-- Post Content -->
        <div class="post-content" style="line-height: 1.8; color: #abb2bf;">
            <?php echo $post['content']; // Content is already HTML, display as-is ?>
        </div>
        </div>
        
        <!-- Post Footer & Author Card -->
        <div class="post-footer" style="margin-top:50px; padding-top:30px; border-top:1px solid #1e2d3d;">
            
            <?php if (!empty($post['footer_content'])): ?>
                <div class="signature-section" style="margin-bottom:30px; color:#ccc; font-style:italic;">
                    <?php echo $post['footer_content']; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($post['show_author_card']) && $post['show_author_card'] == 1): ?>
                <div class="author-card" style="background:#1a2634; padding:20px; border-radius:10px; display:flex; gap:20px; align-items:center; border:1px solid #2c3e50;">
                    <div class="author-avatar">
                        <!-- Try to use Gravatar if URL provided, else system avatar -->
                        <img src="<?php echo !empty($data['admin_settings']['favicon']) ? $data['admin_settings']['favicon'] : 'assets/profile.png'; ?>" 
                             style="width:80px; height:80px; border-radius:50%; border:2px solid var(--primary-color); object-fit:cover;"
                             alt="Author">
                    </div>
                    <div class="author-info">
                        <h4 style="margin-bottom:5px; color:#fff;">Written by <?php echo htmlspecialchars($data['seo']['author'] ?? 'Ziabul Islam'); ?></h4>
                        <p style="color:#abb2bf; font-size:0.95em; margin-bottom:10px;">
                            Thanks for reading! Check out my full profile and other works.
                        </p>
                        <?php if(!empty($post['author_url'])): ?>
                            <a href="<?php echo htmlspecialchars($post['author_url']); ?>" target="_blank" class="btn-sm" style="display:inline-block; border:1px solid var(--accent-color); color:var(--accent-color); border-radius:20px; padding:5px 15px; text-decoration:none;">
                                <i class="fas fa-id-card"></i> View Profile
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </article>

    <!-- Navigation to other posts -->
    <div style="margin-top: 60px; padding-top: 30px; border-top: 1px solid #1e2d3d;">
        <a href="blog.php" class="btn">← All Posts</a>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="js/script.js"></script>

<style>
.post-content h1, .post-content h2, .post-content h3 {
    color: #fff;
    margin-top: 30px;
    margin-bottom: 15px;
}
.post-content h1 { font-size: 32px; }
.post-content h2 { font-size: 28px; }
.post-content h3 { font-size: 24px; }
.post-content p {
    margin-bottom: 20px;
}
.post-content code {
    background: #1e2d3d;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    color: #c778dd;
}
.post-content pre {
    background: #1e2d3d;
    padding: 20px;
    border-radius: 8px;
    overflow-x: auto;
    border: 1px solid #2c3e50;
    margin: 20px 0;
}
.post-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
}
.post-content a {
    color: #c778dd;
    text-decoration: none;
}
.post-content a:hover {
    text-decoration: underline;
}
</style>
</body>
</html>
