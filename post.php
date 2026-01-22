<?php
// Load portfolio data
require_once __DIR__ . '/helpers/data_loader.php';
$data = loadPortfolioData();

$postId = $_GET['id'] ?? null;
$post = null;

if ($postId) {
    foreach ($data['blog_posts'] as $p) {
        if ($p['id'] === $postId) {
            $post = $p;
            break;
        }
    }
}

if (!$post || (isset($post['status']) && $post['status'] !== 'published')) {
    header('Location: blog.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/includes/head.php'; ?>
<body style="padding-top: 100px;">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="container">
    <article class="post-detail" style="max-width: 800px; margin: 0 auto;">
        <div class="section-header" style="margin-bottom: 30px;">
            <h1><span>#</span><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="section-line"></div>
        </div>
        
        <div style="color: var(--primary-color); margin-bottom: 30px;">
            <i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars($post['date']); ?>
        </div>

        <?php if ($post['image']): ?>
        <div class="post-featured-image" style="margin-bottom: 40px; border: 1px solid var(--text-color); padding: 5px;">
            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: auto; display: block;">
        </div>
        <?php endif; ?>

        <div class="post-content" style="line-height: 1.8; color: #abb2bf; font-size: 1.1rem;">
            <?php echo $post['content']; // Outputting raw content because it might contain HTML from admin ?>
        </div>

        <div style="margin-top: 80px; border-top: 1px solid var(--text-color); padding-top: 30px; margin-bottom: 50px;">
            <a href="blog.php" class="btn btn-sm"><i class="fas fa-arrow-left"></i> Back to Blog</a>
        </div>
    </article>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>
