<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['contact_section']['intro'] = sanitizeInput($_POST['intro']);
    $data['contact_section']['message_title'] = sanitizeInput($_POST['message_title']);
    $data['contact_section']['phone'] = sanitizeInput($_POST['phone']);
    $data['contact_section']['email'] = sanitizeInput($_POST['email']);
    $data['contact_section']['email_button_text'] = sanitizeInput($_POST['email_button_text']);

    if (savePortfolioData($data)) {
        setFlashMessage('Contact settings updated successfully!');
        header('Location: edit-contact.php');
        exit;
    } else {
        setFlashMessage('Error saving contact settings', 'error');
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Edit Contact Settings</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="editor-card">
    <form method="POST">
        <div class="form-group">
            <label for="intro">Contact Introduction</label>
            <textarea id="intro" name="intro" required><?php echo htmlspecialchars($data['contact_section']['intro']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="message_title">Message Sidebar Title</label>
            <input type="text" id="message_title" name="message_title" value="<?php echo htmlspecialchars($data['contact_section']['message_title']); ?>" required>
        </div>

        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($data['contact_section']['phone']); ?>">
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($data['contact_section']['email']); ?>" required>
        </div>

        <div class="form-group">
            <label for="email_button_text">Email Button Text</label>
            <input type="text" id="email_button_text" name="email_button_text" value="<?php echo htmlspecialchars($data['contact_section']['email_button_text']); ?>" required>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn-login">Save Contact Info</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
