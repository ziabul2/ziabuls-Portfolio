<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/TestimonialManager.php';
require_once __DIR__ . '/../helpers/AuditLogger.php';

$testimonialManager = new TestimonialManager();
$audit = new AuditLogger();
$flash = getFlashMessage();
$id = $_GET['id'] ?? null;
$item = null;

if ($id) {
    $testimonials = $testimonialManager->getTestimonials();
    foreach ($testimonials as $t) {
        if ($t['id'] === $id) {
            $item = $t;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        die('CSRF token validation failed.');
    }

    $postData = [
        'id' => $_POST['id'] ?: 'test_' . uniqid(),
        'client_name' => sanitizeInput($_POST['client_name']),
        'client_role' => sanitizeInput($_POST['client_role']),
        'content' => sanitizeInput($_POST['content']),
        'image' => sanitizeInput($_POST['image']),
        'updated_at' => time()
    ];

    $testimonials = $testimonialManager->getTestimonials();
    $found = false;
    foreach ($testimonials as &$t) {
        if ($t['id'] === $postData['id']) {
            $t = array_merge($t, $postData);
            $found = true;
            break;
        }
    }
    if (!$found) {
        $postData['created_at'] = time();
        $testimonials[] = $postData;
    }

    if ($testimonialManager->saveTestimonials($testimonials)) {
        $audit->log($id ? "Edit Testimonial" : "Add Testimonial", "Client: " . $postData['client_name']);
        setFlashMessage('Testimonial saved successfully!');
        header('Location: manage-testimonials.php');
        exit;
    } else {
        $audit->log($id ? "Edit Testimonial Failed" : "Add Testimonial Failed", "Client: " . $postData['client_name'], "failed");
        $error = 'Error saving testimonial';
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1><?php echo $id ? 'Edit' : 'Add'; ?> Testimonial</h1>
    <a href="manage-testimonials.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to List</a>
</div>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id ?? ''); ?>">

    <div class="editor-card">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Client Name</label>
                <input type="text" name="client_name" value="<?php echo htmlspecialchars($item['client_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Client Role/Company</label>
                <input type="text" name="client_role" value="<?php echo htmlspecialchars($item['client_role'] ?? ''); ?>" required placeholder="e.g. CEO at TechCorp">
            </div>
        </div>

        <div class="form-group">
            <label>Testimonial Content</label>
            <textarea name="content" rows="5" required><?php echo htmlspecialchars($item['content'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>Client Image</label>
            <div style="display:flex; gap:10px; align-items:center;">
                <img id="client_preview" src="../<?php echo htmlspecialchars($item['image'] ?? 'assets/ziabul islam - non bg.png'); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 1px solid #333;">
                <input type="text" id="client_image" name="image" value="<?php echo htmlspecialchars($item['image'] ?? 'assets/ziabul islam - non bg.png'); ?>" readonly style="flex:1;">
                <button type="button" class="btn-edit" onclick="openMediaPicker('client_image', 'client_preview')">Change</button>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn-login" style="width: auto; padding: 12px 40px;"><i class="fas fa-save"></i> Save Testimonial</button>
        </div>
    </div>
</form>

<?php 
require_once __DIR__ . '/includes/media-picker.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
