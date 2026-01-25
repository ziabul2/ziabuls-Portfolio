<?php
/**
 * Updated manage-blog.php - Uses BlogManager (JSON + DB Hybrid)
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/BlogManager.php';

$blogManager = BlogManager::getInstance();
$flash = getFlashMessage();

// Handle deletion
if (isset($_GET['delete'])) {
    $postId = $_GET['delete'];
    
    if ($blogManager->deletePost($postId)) {
        setFlashMessage('Post deleted successfully!');
        header('Location: manage-blog.php');
        exit;
    } else {
        setFlashMessage('Error deleting post', 'error');
    }
}

// Get all posts logic moved below to handle search
//$posts = $blogManager->getPosts();
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Manage Blog Posts</h1>
    <div style="display:flex; gap:10px;">
        <a href="edit-post.php" class="btn-login" style="width:auto; padding: 10px 20px;">+ Create New Post</a>
        <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
</div>

<?php 
// Handle Search
$search = $_GET['search'] ?? '';
$posts = $blogManager->getPosts();

if ($search) {
    $search = strtolower(trim($search));
    $posts = array_filter($posts, function($p) use ($search) {
        return (
            strpos(strtolower($p['id']), $search) !== false || 
            strpos(strtolower($p['title']), $search) !== false ||
            strpos(strtolower($p['summary']), $search) !== false
        );
    });
}
?>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="editor-card">
    <form method="GET" style="display:flex; gap:10px; margin-bottom:20px;">
        <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search by ID, Title..." style="flex:1; background:#222; border:1px solid #444; padding:10px; color:white;">
        <button type="submit" class="btn-login" style="width:auto;"><i class="fas fa-search"></i> Search</button>
        <?php if($search): ?>
            <a href="manage-blog.php" class="btn-remove" style="position:static; padding:10px 20px;">Clear</a>
        <?php endif; ?>
    </form>

    <table style="width:100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="border-bottom: 1px solid #444; text-align: left;">
                <th style="padding: 15px;">Image</th>
                <th style="padding: 15px; width:120px;">ID</th>
                <th style="padding: 15px;">Title</th>
                <th style="padding: 15px;">Created</th>
                <th style="padding: 15px;">Status</th>
                <th style="padding: 15px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="6" style="padding: 30px; text-align: center; color: #888;">No blog posts found. <a href="edit-post.php">Create one now!</a></td>
                </tr>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <tr style="border-bottom: 1px solid #222;">
                        <td style="padding: 15px;">
                            <?php 
                                $imgSrc = $post['image'];
                                if (!empty($imgSrc) && !str_starts_with($imgSrc, '/') && !str_starts_with($imgSrc, 'http')) {
                                    $imgSrc = '../' . $imgSrc;
                                }
                            ?>
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        </td>
                        <td style="padding: 15px; color: #888; font-family: monospace;">
                            <?php echo htmlspecialchars($post['id']); ?>
                        </td>
                        <td style="padding: 15px;">
                            <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                            <?php if (!empty($post['summary'])): ?>
                                <br><small style="color: #888;"><?php echo substr(htmlspecialchars($post['summary']), 0, 60); ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;"><?php 
                            $dateStr = $post['date'] ?? $post['created_at'] ?? 'now';
                            echo date('M d, Y', strtotime($dateStr)); 
                        ?></td>
                        <td style="padding: 15px;">
                            <span class="status-badge <?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span>
                            <?php
                            // Check for scheduled status
                            $publishedAt = $post['published_at'] ?? ($post['date'] ?? '') . ' ' . ($post['publish_time'] ?? '');
                            $pubTime = strtotime($publishedAt);
                            $now = time();
                            
                            if (($post['status'] === 'draft' || $post['status'] === 'scheduled') && $pubTime > $now) {
                                echo '<div class="scheduled-timer" data-time="' . $pubTime . '"></div>';
                            }
                            ?>
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
.scheduled-timer { font-size: 11px; color: #61afef; margin-top: 5px; font-family: monospace; }
</style>

<script>
function updateTimers() {
    document.querySelectorAll('.scheduled-timer').forEach(el => {
        const target = parseInt(el.getAttribute('data-time')) * 1000;
        const now = new Date().getTime();
        const diff = target - now;
        
        if (diff < 0) {
            el.innerHTML = '<span style="color:#00ff9d">Publishing...</span>';
            return;
        }
        
        const d = Math.floor(diff / (1000 * 60 * 60 * 24));
        const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((diff % (1000 * 60)) / 1000);
        
        let text = 'Live in: ';
        if (d > 0) text += d + 'd ';
        text += h + 'h ' + m + 'm ' + s + 's';
        
        el.innerText = text;
    });
}
setInterval(updateTimers, 1000);
updateTimers();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
