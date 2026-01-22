<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['categories']) && is_array($_POST['categories'])) {
        $data['skills_section']['categories'] = [];
        foreach ($_POST['categories'] as $cat) {
            if (!empty($cat['name'])) {
                // Split items by comma and clean them
                $items = isset($cat['items']) ? explode(',', $cat['items']) : [];
                $items = array_map('trim', $items);
                $items = array_filter($items);
                
                $data['skills_section']['categories'][] = [
                    'name' => sanitizeInput($cat['name']),
                    'items' => array_values($items)
                ];
            }
        }
    }

    if (savePortfolioData($data)) {
        setFlashMessage('Skills updated successfully!');
        header('Location: edit-skills.php');
        exit;
    } else {
        setFlashMessage('Error saving skills', 'error');
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Edit Skills</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<form method="POST">
    <div id="skills-container">
        <?php foreach ($data['skills_section']['categories'] as $index => $cat): ?>
            <div class="editor-card repeater-item">
                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="categories[<?php echo $index; ?>][name]" value="<?php echo htmlspecialchars($cat['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Skills (Comma separated)</label>
                    <input type="text" name="categories[<?php echo $index; ?>][items]" value="<?php echo htmlspecialchars(implode(', ', $cat['items'])); ?>" required>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button type="button" class="btn-add" id="add-category">+ Add New Category</button>

    <div style="margin: 40px 0; text-align: right;">
        <button type="submit" class="btn-login" style="width: 200px;">Save All Skills</button>
    </div>
</form>

<script>
let catCount = <?php echo count($data['skills_section']['categories']); ?>;
document.getElementById('add-category').addEventListener('click', function() {
    const container = document.getElementById('skills-container');
    const div = document.createElement('div');
    div.className = 'editor-card repeater-item';
    div.innerHTML = `
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">Remove</button>
        <div class="form-group">
            <label>Category Name</label>
            <input type="text" name="categories[${catCount}][name]" required>
        </div>
        <div class="form-group">
            <label>Skills (Comma separated)</label>
            <input type="text" name="categories[${catCount}][items]" required>
        </div>
    `;
    container.appendChild(div);
    catCount++;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
