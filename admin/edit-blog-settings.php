<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['blog_section']['title'] = sanitizeInput($_POST['title']);
    $data['blog_section']['view_all_text'] = sanitizeInput($_POST['view_all_text']);
    $data['blog_section']['view_all_link'] = sanitizeInput($_POST['view_all_link']);

    if (savePortfolioData($data)) {
        setFlashMessage('Blog section settings updated successfully!');
        header('Location: edit-blog-settings.php');
        exit;
    } else {
        setFlashMessage('Error saving settings', 'error');
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Edit Blog Section UI</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="editor-card">
        <div class="form-group">
            <label for="title">Section Title (shows as #title)</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($data['blog_section']['title']); ?>" required>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="view_all_text">View All Button Text</label>
                <input type="text" id="view_all_text" name="view_all_text" value="<?php echo htmlspecialchars($data['blog_section']['view_all_text']); ?>" required>
            </div>
            <div class="form-group">
                <label for="view_all_link">View All Button Link</label>
                <input type="text" id="view_all_link" name="view_all_link" value="<?php echo htmlspecialchars($data['blog_section']['view_all_link']); ?>" required>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn-login" style="width: auto; padding: 10px 30px;">Save Settings</button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
