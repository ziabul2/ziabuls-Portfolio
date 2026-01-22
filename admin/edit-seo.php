<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization
    $data['seo']['title'] = sanitizeInput($_POST['title']);
    $data['seo']['favicon'] = sanitizeInput($_POST['favicon']);
    
    // Handle stylesheets (repeatable fields)
    if (isset($_POST['stylesheets']) && is_array($_POST['stylesheets'])) {
        $data['seo']['stylesheets'] = array_map('sanitizeInput', array_filter($_POST['stylesheets']));
    }

    if (savePortfolioData($data)) {
        setFlashMessage('SEO settings updated successfully!');
        header('Location: edit-seo.php');
        exit;
    } else {
        setFlashMessage('Error saving SEO settings', 'error');
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Edit SEO & Site Settings</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="editor-card">
    <form method="POST">
        <div class="form-group">
            <label for="title">Site Title</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($data['seo']['title']); ?>" required>
        </div>

        <div class="form-group">
            <label for="favicon">Favicon Path</label>
            <input type="text" id="favicon" name="favicon" value="<?php echo htmlspecialchars($data['seo']['favicon']); ?>" required>
        </div>

        <h3>Stylesheets</h3>
        <div id="stylesheets-container">
            <?php foreach ($data['seo']['stylesheets'] as $index => $sheet): ?>
                <div class="form-group repeater-item">
                    <input type="text" name="stylesheets[]" value="<?php echo htmlspecialchars($sheet); ?>" required>
                    <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn-add" id="add-stylesheet">+ Add Stylesheet</button>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn-login">Save Changes</button>
        </div>
    </form>
</div>

<script>
document.getElementById('add-stylesheet').addEventListener('click', function() {
    const container = document.getElementById('stylesheets-container');
    const div = document.createElement('div');
    div.className = 'form-group repeater-item';
    div.innerHTML = `
        <input type="text" name="stylesheets[]" required>
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
    `;
    container.appendChild(div);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
