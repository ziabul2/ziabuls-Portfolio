<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['projects']) && is_array($_POST['projects'])) {
        $data['projects_section']['items'] = [];
        foreach ($_POST['projects'] as $item) {
            if (!empty($item['title'])) {
                $data['projects_section']['items'][] = [
                    'title' => sanitizeInput($item['title']),
                    'technologies' => sanitizeInput($item['technologies']),
                    'description' => sanitizeInput($item['description']),
                    'link_text' => sanitizeInput($item['link_text']),
                    'link_url' => sanitizeInput($item['link_url']),
                    'image' => sanitizeInput($item['image'])
                ];
            }
        }
    }

    if (savePortfolioData($data)) {
        setFlashMessage('Projects updated successfully!');
        header('Location: edit-projects.php');
        exit;
    } else {
        setFlashMessage('Error saving projects', 'error');
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Edit Projects</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<form method="POST">
    <div id="projects-container">
        <?php foreach ($data['projects_section']['items'] as $index => $item): ?>
            <div class="editor-card repeater-item">
                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
                <div class="form-group">
                    <label>Project Title</label>
                    <input type="text" name="projects[<?php echo $index; ?>][title]" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Technologies (Comma separated)</label>
                    <input type="text" name="projects[<?php echo $index; ?>][technologies]" value="<?php echo htmlspecialchars($item['technologies']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="projects[<?php echo $index; ?>][description]" required><?php echo htmlspecialchars($item['description']); ?></textarea>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Link Text</label>
                        <input type="text" name="projects[<?php echo $index; ?>][link_text]" value="<?php echo htmlspecialchars($item['link_text']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Link URL</label>
                        <input type="text" name="projects[<?php echo $index; ?>][link_url]" value="<?php echo htmlspecialchars($item['link_url']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Project Image</label>
                    <img id="proj_img_preview_<?php echo $index; ?>" src="../<?php echo htmlspecialchars($item['image']); ?>" class="image-preview">
                    <div class="image-picker-controls">
                        <input type="text" id="proj_image_<?php echo $index; ?>" name="projects[<?php echo $index; ?>][image]" value="<?php echo htmlspecialchars($item['image']); ?>" readonly style="background:#111;">
                        <button type="button" class="btn-edit" onclick="openMediaPicker('proj_image_<?php echo $index; ?>', 'proj_img_preview_<?php echo $index; ?>')">Change Image</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button type="button" class="btn-add" id="add-project">+ Add New Project</button>

    <div style="margin: 40px 0; text-align: right;">
        <button type="submit" class="btn-login" style="width: 200px;">Save All Projects</button>
    </div>
</form>

<script>
let projectCount = <?php echo count($data['projects_section']['items']); ?>;
document.getElementById('add-project').addEventListener('click', function() {
    const container = document.getElementById('projects-container');
    const div = document.createElement('div');
    div.className = 'editor-card repeater-item';
    div.innerHTML = `
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
        <div class="form-group">
            <label>Project Title</label>
            <input type="text" name="projects[${projectCount}][title]" required>
        </div>
        <div class="form-group">
            <label>Technologies</label>
            <input type="text" name="projects[${projectCount}][technologies]" required>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="projects[${projectCount}][description]" required></textarea>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Link Text</label>
                <input type="text" name="projects[${projectCount}][link_text]" required>
            </div>
            <div class="form-group">
                <label>Link URL</label>
                <input type="text" name="projects[${projectCount}][link_url]" required>
            </div>
        </div>
        <div class="form-group">
            <label>Project Image</label>
            <img id="proj_img_preview_${currentCount}" src="../assets/project1.png" class="image-preview">
            <div class="image-picker-controls">
                <input type="text" id="proj_image_${currentCount}" name="projects[${currentCount}][image]" value="assets/project1.png" readonly style="background:#111;">
                <button type="button" class="btn-edit" onclick="openMediaPicker('proj_image_${currentCount}', 'proj_img_preview_${currentCount}')">Change Image</button>
            </div>
        </div>
    `;
    container.appendChild(div);
    projectCount++;
});
</script>

<?php 
require_once __DIR__ . '/includes/media-picker.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
