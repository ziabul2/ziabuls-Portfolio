<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();
$posts = $data['blog_posts'] ?? [];

// Handle deletion
if (isset($_GET['delete'])) {
    $postId = $_GET['delete'];
    $data['blog_posts'] = array_filter($data['blog_posts'], function($post) use ($postId) {
        return $post['id'] !== $postId;
    });
    
    if (savePortfolioData($data)) {
        setFlashMessage('Post deleted successfully!');
        header('Location: manage-blog.php');
        exit;
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Manage Blog Posts</h1>
    <div style="display:flex; gap:10px;">
        <a href="edit-post.php" class="btn-login" style="width:auto; padding: 10px 20px;">+ Create New Post</a>
        <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="editor-card">
    <table style="width:100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="border-bottom: 1px solid #444; text-align: left;">
                <th style="padding: 15px;">Image</th>
                <th style="padding: 15px;">Title</th>
                <th style="padding: 15px;">Date</th>
                <th style="padding: 15px;">Status</th>
                <th style="padding: 15px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="5" style="padding: 30px; text-align: center; color: #888;">No blog posts found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <tr style="border-bottom: 1px solid #222;">
                        <td style="padding: 15px;">
                            <img src="../<?php echo htmlspecialchars($post['image']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        </td>
                        <td style="padding: 15px;"><?php echo htmlspecialchars($post['title']); ?></td>
                        <td style="padding: 15px;"><?php echo htmlspecialchars($post['date']); ?></td>
                        <td style="padding: 15px;">
                            <span class="status-badge <?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span>
                        </td>
                        <td style="padding: 15px; text-align: right;">
                            <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn-edit" style="margin-right: 10px;">Edit</a>
                            <a href="manage-blog.php?delete=<?php echo $post['id']; ?>" class="btn-remove" style="position:static;" onclick="return confirm('Are you sure you want to delete this post?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.status-badge {
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    background: rgba(255,255,255,0.1);
}
.status-badge.published { background: rgba(0, 255, 157, 0.2); color: #00ff9d; }
.status-badge.draft { background: rgba(255, 191, 0, 0.2); color: #ffbf00; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
