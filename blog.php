<?php
/**
 * Updated blog.php - Fetches blog posts from MySQL database
 */
// Load portfolio data for static sections (hero, navbar, footer, etc.)
require_once __DIR__ . '/helpers/data_loader.php';
require_once __DIR__ . '/helpers/DatabaseManager.php';

$data = loadPortfolioData();

// Fetch blog posts via BlogManager (Hybrid: JSON Primary)
require_once __DIR__ . '/helpers/BlogManager.php';
$blogManager = BlogManager::getInstance();
$blog_posts = $blogManager->getPosts(['status' => 'published']);
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/includes/head.php'; ?>
<body style="padding-top: 100px;">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="container">
    <div class="section-header" style="margin-bottom: 50px;">
        <h1><span>#</span>blog</h1>
        <div class="section-line"></div>
    </div>

    <div class="project-grid">
        <?php if (empty($blog_posts)): ?>
            <p style="color: #666; font-style: italic;">Stay tuned for new articles!</p>
        <?php else: ?>
            <?php foreach ($blog_posts as $post): ?>
            <div class="project-card">
                <div class="project-img">
                    <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                </div>
                <div class="project-tech"><?php echo date('M d, Y', strtotime($post['date'] ?? $post['created_at'] ?? 'now')); ?></div>
                <div class="project-content">
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    <p><?php echo htmlspecialchars($post['summary']); ?></p>
                    <a href="post.php?id=<?php echo urlencode($post['id']); ?>" class="btn btn-sm">Read More</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>
