<?php
/**
 * Updated edit-post.php - Uses BlogManager (JSON + DB Hybrid)
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/BlogManager.php';

$blogManager = BlogManager::getInstance();
$flash = getFlashMessage();
$postId = $_GET['id'] ?? null;
$post = null;

if ($postId) {
    $post = $blogManager->getPost($postId);
}

// Default values for new post
if (!$post) {
    $post = [
        'id' => '',
        'title' => '',
        'summary' => '',
        'content' => '',
        'image' => 'assets/project1.png',
        'status' => 'draft',
        'date' => date('Y-m-d')
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation (optional)
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    $newPost = [
        'id' => sanitizeInput($_POST['post_id']),
        'title' => sanitizeInput($_POST['title']),
        'summary' => sanitizeInput($_POST['summary']),
        'content' => $_POST['content'], // Keep HTML
        'image' => sanitizeInput($_POST['image']),
        'video_url' => sanitizeInput($_POST['video_url'] ?? ''),
        'status' => sanitizeInput($_POST['status']),
        'date' => $_POST['date'] ?? date('Y-m-d'), 
        'publish_time' => $_POST['publish_time'] ?? date('H:i'), // New Time field
        'seo_title' => sanitizeInput($_POST['seo_title'] ?? ''),
        'seo_desc' => sanitizeInput($_POST['seo_desc'] ?? ''),
        'seo_tags' => sanitizeInput($_POST['seo_tags'] ?? ''),
        'footer_content' => $_POST['footer_content'] ?? '', // Allow HTML keys
        'author_url' => sanitizeInput($_POST['author_url'] ?? ''),
        'show_author_card' => sanitizeInput($_POST['show_author_card'] ?? 0),
        'author_id' => 1
    ];

    // Combine date and time for sorting/scheduling if needed
    $newPost['published_at'] = $newPost['date'] . ' ' . $newPost['publish_time'];

    if (!$postId) {
        // Create new post: Generate ID "01 - Title"
        if (empty($newPost['id'])) {
            // Find highest existing index
            $posts = $blogManager->getPosts();
            $maxNum = 0;
            foreach ($posts as $p) {
                // Check if ID starts with digits followed by " - " or just digits
                if (preg_match('/^(\d+)/', $p['id'], $matches)) {
                    $num = intval($matches[1]);
                    if ($num > $maxNum) $maxNum = $num;
                }
            }
            $nextNum = $maxNum + 1;
            
            // Format: 01 - Title (sanitize title slightly, keep readable but URL safe-ish? User used spaces in example)
            // User example: "01 - Demo post" -> spaces allowed.
            // Let's keep spaces if user wants, but maybe safer to be filename friendly?
            // "fix this and set this as a number also title, like 01 - Demo post"
            // I will strictly follow "01 - Title".
            $newPost['id'] = sprintf('%02d - %s', $nextNum, $newPost['title']);
        }
    } else {
        // If ID changed? JSON array logic in savePost handles keys by ID value.
        // We generally assume ID doesn't change or we are editing the record matching that ID.
    }

    if ($blogManager->savePost($newPost)) {
        setFlashMessage('Post saved successfully!');
        header('Location: manage-blog.php');
        exit;
    } else {
        setFlashMessage('Error saving post', 'error');
    }
}

// Generate CSRF token
require_once __DIR__ . '/../config/security.php';
$csrfToken = generateCSRFToken();
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1><?php echo $postId ? 'Edit Post' : 'Create New Post'; ?></h1>
    <a href="manage-blog.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Blog Manager</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <div class="editor-card">
        <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <div class="main-fields">
                <div class="form-group">
                    <label for="title">Post Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="post_id">Slug / ID (URL friendly)</label>
                    <input type="text" id="post_id" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>" placeholder="auto-generated-if-empty" <?php echo $postId ? 'readonly' : ''; ?>>
                </div>

                <div class="form-group">
                    <label for="date">Publish Date</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($post['date'] ?? date('Y-m-d')); ?>">
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
                    <?php 
                        $imgSrc = $post['image'];
                        if (!empty($imgSrc) && !str_starts_with($imgSrc, '/') && !str_starts_with($imgSrc, 'http')) {
                            $imgSrc = '../' . $imgSrc;
                        }
                    ?>
                    <img id="post_img_preview" src="<?php echo htmlspecialchars($imgSrc); ?>" class="image-preview" style="width:100%; height: 200px;">
                    <div class="image-picker-controls">
                        <input type="text" id="post_image" name="image" value="<?php echo htmlspecialchars($post['image']); ?>" readonly style="background:#111;">
                        <button type="button" class="btn-edit" onclick="openMediaPicker('post_image', 'post_img_preview')">Change</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="video_url">Video Embed URL (YouTube/Vimeo)</label>
                    <input type="text" id="video_url" name="video_url" value="<?php echo htmlspecialchars($post['video_url'] ?? ''); ?>" placeholder="https://www.youtube.com/embed/...">
                </div>

                <div class="editor-card" style="margin-top:20px; padding:15px; border-left:3px solid var(--accent-color);">
                    <h3><i class="fas fa-search"></i> SEO Settings</h3>
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" name="seo_title" value="<?php echo htmlspecialchars($post['seo_title'] ?? ''); ?>" placeholder="Default: Post Title">
                    </div>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea name="seo_desc" style="height:60px;"><?php echo htmlspecialchars($post['seo_desc'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Tags (comma separated)</label>
                        <input type="text" name="seo_tags" value="<?php echo htmlspecialchars($post['seo_tags'] ?? ''); ?>" placeholder="tech, news, update">
                    </div>
                </div>

                <div class="editor-card" style="margin-top:20px; padding:15px; border-left:3px solid #ffbf00;">
                    <h3><i class="fas fa-clock"></i> Publishing</h3>
                    
                    <div style="margin-bottom:15px;">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="date" id="post_date" value="<?php echo htmlspecialchars($post['date'] ?? date('Y-m-d')); ?>">
                        </div>
                         <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="publish_time" id="post_time" value="<?php echo htmlspecialchars($post['publish_time'] ?? date('H:i')); ?>">
                        </div>
                    </div>

                    <input type="hidden" name="status" id="post_status" value="<?php echo htmlspecialchars($post['status']); ?>">
                    
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <!-- Draft -->
                        <button type="button" class="btn-edit" onclick="setStatus('draft')" style="background:#555; border:none;">
                            <i class="fas fa-save"></i> Save Draft
                        </button>
                        
                        <!-- Schedule -->
                        <button type="button" class="btn-edit" onclick="setStatus('scheduled')" style="background:#d4a017; border:none; color:black;">
                            <i class="fas fa-calendar-alt"></i> Schedule
                        </button>
                        
                        <!-- Publish -->
                        <button type="button" class="btn-login" onclick="setStatus('published')" style="width:100%;">
                            <i class="fas fa-paper-plane"></i> Publish Now
                        </button>
                    </div>
                    
                    <div id="status_feedback" style="margin-top:10px; text-align:center; font-size:0.9em; color:#888;">
                        Current Status: <strong><?php echo ucfirst($post['status']); ?></strong>
                    </div>
                </div>

                <div class="editor-card" style="margin-top:20px; padding:15px; border-left:3px solid #61afef;">
                    <h3><i class="fas fa-id-card"></i> Author & Footer</h3>
                    
                    <div class="form-group">
                        <label>Signature / Footer Text <span class="tooltip-icon" data-tip="Appears at the bottom of the post">?</span></label>
                        <textarea name="footer_content" style="height:80px;" placeholder="e.g. Written by Zimbabu..."><?php echo htmlspecialchars($post['footer_content'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Author Card Link (Gravatar) <span class="tooltip-icon" data-tip="Link to your Gravatar or Portfolio profile">?</span></label>
                        <input type="text" name="author_url" value="<?php echo htmlspecialchars($post['author_url'] ?? 'https://gravatar.com/dreamilyavenue565656b48d'); ?>" placeholder="https://gravatar.com/...">
                    </div>
                    
                    <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                         <input type="checkbox" id="show_author_card" name="show_author_card" value="1" <?php echo ($post['show_author_card'] ?? '1') == '1' ? 'checked' : ''; ?>>
                         <label for="show_author_card" style="margin:0; cursor:pointer;">Show Author Card?</label>
                    </div>
                </div>

                <!-- Hidden submit button triggered by JS -->
                <button type="submit" id="real_submit" style="display:none;"></button>

                <script>
                function setStatus(status) {
                    const statusInput = document.getElementById('post_status');
                    statusInput.value = status;
                    
                    if (status === 'published') {
                        // Optional: Reset date to now? User might want to Publish Now but keep backdated date.
                        // "Publish Now" usually implies NOW.
                        if (confirm('Publish this post immediately?')) {
                            document.getElementById('real_submit').click();
                        }
                    } else if (status === 'scheduled') {
                        // Validate future date
                        // Simple check or just let backend handle
                        document.getElementById('real_submit').click();
                    } else {
                        // Draft
                        document.getElementById('real_submit').click();
                    }
                }
                </script>
            </div>
        </div>
    </div>
</form>

<?php 
require_once __DIR__ . '/includes/media-picker.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
