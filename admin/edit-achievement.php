<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../helpers/AchievementManager.php';
require_once __DIR__ . '/includes/functions.php';

$achievementManager = new AchievementManager();
$id = $_GET['id'] ?? null;
$item = null;
$flashMessage = '';

if ($id) {
    $item = $achievementManager->getAchievement($id);
    if (!$item) {
        header('Location: manage-achievements.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        die('CSRF token validation failed. Please refresh the page and try again.');
    }

    $postData = [
        'id' => $_POST['id'] ?: uniqid('ach_'),
        'title' => sanitizeInput($_POST['title']),
        'short_description' => sanitizeInput($_POST['short_description']),
        'long_description' => $_POST['long_description'], // Admin trusted input (TinyMCE HTML)
        'completion_date' => sanitizeInput($_POST['completion_date']),
        'organization' => sanitizeInput($_POST['organization']),
        'category' => sanitizeInput($_POST['category']),
        'verification_link' => sanitizeInput($_POST['verification_link']),
        'database_subject' => sanitizeInput($_POST['database_subject'] ?? '')
    ];

    // Image Upload Handling
    $certificate_image = $_POST['existing_image'] ?? '';
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['size'] > 0) {
        $uploadResult = handleFileUpload($_FILES['image_upload'], '../assets/');
        if (is_array($uploadResult) && isset($uploadResult['error'])) {
            $flashMessage = "<div class='error-msg'>" . $uploadResult['error'] . "</div>";
        } else {
            $certificate_image = $uploadResult; // function returns the relative path string directly on success
        }
    } elseif (!empty($_POST['image_url'])) { // Fallback if URL is provided
        $certificate_image = sanitizeInput($_POST['image_url']);
    }

    $postData['certificate_image'] = $certificate_image;

    if (empty($flashMessage)) {
        if ($achievementManager->saveAchievement($postData)) {
            setFlashMessage('Achievement saved successfully!');
            header('Location: manage-achievements.php');
            exit;
        } else {
            $flashMessage = "<div class='error-msg'>Failed to save achievement.</div>";
        }
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1><?php echo $item ? 'Edit Achievement' : 'Add New Achievement'; ?></h1>
    <a href="manage-achievements.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<?php if ($flashMessage) echo $flashMessage; ?>

<div class="editor-card">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id'] ?? ''); ?>">
        <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($item['certificate_image'] ?? ''); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

        <div class="form-group">
            <label>Title <span style="color:var(--error-color)">*</span></label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>" required>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            <div class="form-group">
                <label>Organization / Issuer <span style="color:var(--error-color)">*</span></label>
                <input type="text" name="organization" value="<?php echo htmlspecialchars($item['organization'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Completion Date <span style="color:var(--error-color)">*</span></label>
                <input type="date" name="completion_date" value="<?php echo htmlspecialchars($item['completion_date'] ?? ''); ?>" required>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            <div class="form-group">
                <label>Category <span style="color:var(--error-color)">*</span></label>
                <select name="category" required style="width:100%; padding:10px; background:#222; border:1px solid #444; color:white; border-radius:4px;">
                    <?php 
                    $categories = ['Academic', 'Programming', 'Competition', 'Other'];
                    foreach($categories as $cat) {
                        $selected = ($item['category'] ?? '') === $cat ? 'selected' : '';
                        echo "<option value=\"$cat\" $selected>$cat</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Verification Link (Optional)</label>
                <input type="url" name="verification_link" value="<?php echo htmlspecialchars($item['verification_link'] ?? ''); ?>" placeholder="https://...">
            </div>
        </div>

        <!-- Certificate Image -->
        <div class="form-group" style="padding: 20px; background: rgba(0,0,0,0.2); border: 1px dashed #555; border-radius: 4px; margin-top:20px;">
            <label><i class="fas fa-image"></i> Certificate / Badge Image</label>
            <?php if (!empty($item['certificate_image'])): ?>
                <div style="margin-bottom: 15px;">
                    <img src="../<?php echo htmlspecialchars($item['certificate_image']); ?>" style="max-height: 150px; border-radius: 4px;">
                </div>
            <?php endif; ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="font-size: 0.9em; color:#aaa;">Upload New Image</label>
                    <input type="file" name="image_upload" accept="image/*">
                </div>
                <div>
                    <label style="font-size: 0.9em; color:#aaa;">Or Provide Image URL</label>
                    <input type="text" name="image_url" placeholder="https://..." value="<?php echo htmlspecialchars($item['certificate_image'] ?? ''); ?>">
                </div>
            </div>
            <small style="color:#888; display:block; margin-top:10px;">Supported formats: JPG, PNG, WEBP. Max size: 2MB.</small>
        </div>

        <div class="form-group" style="margin-top:20px;">
            <label>Short Description (Preview) <span style="color:var(--error-color)">*</span></label>
            <textarea name="short_description" rows="3" required placeholder="A brief 1-2 sentence summary..."><?php echo htmlspecialchars($item['short_description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>Detailed Description <span style="color:var(--error-color)">*</span></label>
            <textarea name="long_description" id="long_description" rows="10" placeholder="Full details, skills learned, or project scope..."><?php echo htmlspecialchars($item['long_description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group" style="padding: 20px; background: rgba(97, 175, 239, 0.05); border-left: 4px solid #61afef; border-radius: 4px;">
            <label style="color: #61afef;"><i class="fas fa-database"></i> Database Subject (Optional)</label>
            <input type="text" name="database_subject" value="<?php echo htmlspecialchars($item['database_subject'] ?? ''); ?>" placeholder="Related Database/Backend subject context">
            <small style="color:#888; display:block; margin-top:5px;">This field is strictly for tracking database related context and will only display if populated.</small>
        </div>

        <button type="submit" class="btn-login" style="width: auto; padding: 12px 30px;"><i class="fas fa-save"></i> Save Achievement</button>
    </form>
</div>

<!-- TinyMCE Rich Text Editor -->
<script src="https://cdn.tiny.cloud/1/t75cgwgnh286rc419ay05y598d7zqdyd0yda8zypmlz62p65/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#long_description',
        plugins: 'lists link image code table wordcount',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link code',
        menubar: false,
        height: 300,
        skin: 'oxide-dark',
        content_css: 'dark',
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
