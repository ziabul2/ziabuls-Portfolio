<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/TestimonialManager.php';
require_once __DIR__ . '/../helpers/AuditLogger.php';

$testimonialManager = new TestimonialManager();
$audit = new AuditLogger();
$flash = getFlashMessage();

// Handle Delete
if (isset($_GET['delete'])) {
    $token = $_GET['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        die('CSRF token validation failed.');
    }
    
    if ($testimonialManager->deleteTestimonial($_GET['delete'])) {
        $audit->log("Delete Testimonial", "ID: " . $_GET['delete']);
        setFlashMessage('Testimonial deleted successfully!');
    } else {
        $audit->log("Delete Testimonial Failed", "ID: " . $_GET['delete'], "failed");
        setFlashMessage('Error deleting testimonial', 'error');
    }
    header('Location: manage-testimonials.php');
    exit;
}

$testimonials = $testimonialManager->getTestimonials();
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Manage Testimonials</h1>
    <div style="display:flex; gap:10px;">
        <a href="edit-testimonial.php" class="btn-edit" style="background: var(--accent-color); color: #fff; border:none;"><i class="fas fa-plus"></i> Add New</a>
        <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="editor-card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #444; text-align: left;">
                    <th style="padding: 15px;">Client</th>
                    <th style="padding: 15px;">Testimonial</th>
                    <th style="padding: 15px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($testimonials)): ?>
                    <tr>
                        <td colspan="3" style="padding: 30px; text-align: center; color: #888;">No testimonials found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($testimonials as $item): ?>
                        <tr style="border-bottom: 1px solid #222;">
                            <td style="padding: 15px; display: flex; align-items: center; gap: 15px;">
                                <img src="../<?php echo htmlspecialchars($item['image'] ?? 'assets/ziabul islam - non bg.png'); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <div>
                                    <div style="font-weight: bold;"><?php echo htmlspecialchars($item['client_name']); ?></div>
                                    <div style="font-size: 0.8em; color: #888;"><?php echo htmlspecialchars($item['client_role']); ?></div>
                                </div>
                            </td>
                            <td style="padding: 15px; color: #ccc; font-size: 0.9em; max-width: 400px;">
                                "<?php echo htmlspecialchars(substr($item['content'], 0, 100)) . (strlen($item['content']) > 100 ? '...' : ''); ?>"
                            </td>
                            <td style="padding: 15px; text-align: right;">
                                <a href="edit-testimonial.php?id=<?php echo urlencode($item['id']); ?>" class="btn-edit" style="margin-right:5px;"><i class="fas fa-edit"></i></a>
                                <a href="manage-testimonials.php?delete=<?php echo urlencode($item['id']); ?>&csrf_token=<?php echo generateCSRFToken(); ?>" class="btn-remove" style="position:static;" onclick="return confirm('Delete this testimonial?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
