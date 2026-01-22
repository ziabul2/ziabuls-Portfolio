<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();
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

// Default values for new post
if (!$post) {
    $post = [
        'id' => '',
        'title' => '',
        'summary' => '',
        'content' => '',
        'date' => date('Y-m-d'),
        'image' => 'assets/project1.png',
        'status' => 'draft'
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPost = [
        'id' => sanitizeInput($_POST['post_id']),
        'title' => sanitizeInput($_POST['title']),
        'summary' => sanitizeInput($_POST['summary']),
        'content' => $_POST['content'], // Keep HTML if needed, but consider sanitizing specifically
        'date' => sanitizeInput($_POST['date']),
        'image' => sanitizeInput($_POST['image']),
        'status' => sanitizeInput($_POST['status'])
    ];

    if (!$postId) {
        // Create new
        if (empty($newPost['id'])) {
            $newPost['id'] = strtolower(str_replace(' ', '-', $newPost['title']));
        }
        $data['blog_posts'][] = $newPost;
    } else {
        // Update existing
        foreach ($data['blog_posts'] as $key => $p) {
            if ($p['id'] === $postId) {
                $data['blog_posts'][$key] = $newPost;
                break;
            }
        }
    }

    if (savePortfolioData($data)) {
        setFlashMessage('Post saved successfully!');
        header('Location: manage-blog.php');
        exit;
    } else {
        setFlashMessage('Error saving post', 'error');
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1><?php echo $postId ? 'Edit Post' : 'Create New Post'; ?></h1>
    <a href="manage-blog.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Blog Manager</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="editor-card">
        <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <div class="main-fields">
                <div class="form-group">
                    <label for="title">Post Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="post_id">Slug / ID (URL friendly)</label>
                    <input type="text" id="post_id" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>" placeholder="auto-generated-if-empty">
                </div>

                <div class="form-group">
                    <label for="summary">Summary / Excerpt</label>
                    <textarea id="summary" name="summary" style="height: 100px;"><?php echo htmlspecialchars($post['summary']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="content">Full Content (HTML/Markdown supported)</label>
                    <textarea id="content" name="content" style="height: 400px; font-family: monospace;"><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>
            </div>

            <div class="sidebar-fields">
                <div class="form-group">
                    <label>Featured Image</label>
                    <img id="post_img_preview" src="../<?php echo htmlspecialchars($post['image']); ?>" class="image-preview" style="width:100%; height: 200px;">
                    <div class="image-picker-controls">
                        <input type="text" id="post_image" name="image" value="<?php echo htmlspecialchars($post['image']); ?>" readonly style="background:#111;">
                        <button type="button" class="btn-edit" onclick="openMediaPicker('post_image', 'post_img_preview')">Change</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="date">Publish Date</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($post['date']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" style="width:100%; padding:10px; background:#111; color:white; border:1px solid #444; border-radius:4px;">
                        <option value="draft" <?php echo $post['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo $post['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                </div>

                <div style="margin-top: 50px;">
                    <button type="submit" class="btn-login" style="width:100%;">Save Post</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php 
require_once __DIR__ . '/includes/media-picker.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
