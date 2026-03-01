<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();

// Ensure section exists (backward compatibility or fresh setup)
if (!isset($data['achievements_section'])) {
    $data['achievements_section'] = [
        'title' => 'latest-achievements',
        'view_all_text' => 'View all ~~>',
        'view_all_link' => 'achievements.php'
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['achievements_section'] = [
        'title' => sanitizeInput($_POST['title']),
        'view_all_text' => sanitizeInput($_POST['view_all_text']),
        'view_all_link' => sanitizeInput($_POST['view_all_link'])
    ];

    if (savePortfolioData($data)) {
        setFlashMessage('Achievements section settings updated successfully!');
        header('Location: edit-achievements-settings.php');
        exit;
    } else {
        setFlashMessage('Error saving settings', 'error');
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Edit Achievements Section UI</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="editor-card">
    <h3 style="margin-bottom: 20px;"><i class="fas fa-paint-brush"></i> Section Appearance</h3>
    <form method="POST">
        <div class="form-group">
            <label>Section Title</label>
            <div style="display:flex; align-items:center;">
                <span style="color:var(--accent-color); font-size:1.5em; margin-right:10px;">#</span>
                <input type="text" name="title" value="<?php echo htmlspecialchars($data['achievements_section']['title']); ?>" required style="flex:1;">
            </div>
            <small style="color:#aaa; margin-top:5px; display:block;">The heading shown on the homepage.</small>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>View All Button Text</label>
                <input type="text" name="view_all_text" value="<?php echo htmlspecialchars($data['achievements_section']['view_all_text']); ?>" required>
            </div>
            <div class="form-group">
                <label>View All Button Link</label>
                <input type="text" name="view_all_link" value="<?php echo htmlspecialchars($data['achievements_section']['view_all_link']); ?>" required>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn-login" style="width: auto; padding: 12px 30px;"><i class="fas fa-save"></i> Save Settings</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
