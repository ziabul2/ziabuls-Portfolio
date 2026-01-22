<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();
$assets = getAvailableAssets();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Header
    $data['footer']['logo_text'] = sanitizeInput($_POST['header_logo'] ?? '');
    $data['footer']['role'] = sanitizeInput($_POST['header_role'] ?? '');
    
    // Handle Footer
    $data['footer']['email'] = sanitizeInput($_POST['footer_email'] ?? '');
    $data['footer']['media_title'] = sanitizeInput($_POST['footer_media_title'] ?? '');
    $data['footer']['copyright_text'] = sanitizeInput($_POST['footer_copyright'] ?? '');

    if (savePortfolioData($data)) {
        setFlashMessage('Site settings updated successfully!');
        header('Location: edit-site.php');
        exit;
    } else {
        setFlashMessage('Error saving site settings', 'error');
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Edit Site UI (Header & Footer)</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="editor-card">
        <h2>Header Configuration</h2>
        <div class="form-group">
            <label for="header_logo">Logo Text</label>
            <input type="text" id="header_logo" name="header_logo" value="<?php echo htmlspecialchars($data['footer']['logo_text']); ?>" required>
        </div>
        <div class="form-group">
            <label for="header_role">Role Display (Sub-logo)</label>
            <input type="text" id="header_role" name="header_role" value="<?php echo htmlspecialchars($data['footer']['role']); ?>" required>
        </div>
    </div>

    <div class="editor-card">
        <h2>Footer Configuration</h2>
        <div class="form-group">
            <label for="footer_email">Footer Email</label>
            <input type="email" id="footer_email" name="footer_email" value="<?php echo htmlspecialchars($data['footer']['email']); ?>" required>
        </div>
        <div class="form-group">
            <label for="footer_media_title">Social Media Title</label>
            <input type="text" id="footer_media_title" name="footer_media_title" value="<?php echo htmlspecialchars($data['footer']['media_title']); ?>" required>
        </div>
        <div class="form-group">
            <label for="footer_copyright">Copyright Text</label>
            <input type="text" id="footer_copyright" name="footer_copyright" value="<?php echo htmlspecialchars($data['footer']['copyright_text']); ?>" required>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: right;">
        <button type="submit" class="btn-login" style="width: 200px;">Save Site Settings</button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
