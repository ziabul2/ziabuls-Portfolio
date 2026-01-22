<?php
// Load portfolio data
require_once __DIR__ . '/helpers/data_loader.php';
$data = loadPortfolioData();
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
        <?php 
        $published_posts = array_filter($data['blog_posts'], function($post) {
            return isset($post['status']) && $post['status'] === 'published';
        });
        usort($published_posts, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        if (empty($published_posts)): ?>
            <p style="color: #666; font-style: italic;">Stay tuned for new articles!</p>
        <?php else: ?>
            <?php foreach ($published_posts as $post): ?>
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
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>
