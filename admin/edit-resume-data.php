<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/AuditLogger.php';

$audit = new AuditLogger();
$data = getPortfolioData();
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        die('CSRF token validation failed.');
    }

    // Handle Personal Info
    $data['resume_data']['personal_info'] = [
        'father_name' => sanitizeInput($_POST['father_name']),
        'mother_name' => sanitizeInput($_POST['mother_name']),
        'dob' => sanitizeInput($_POST['dob']),
        'nid' => sanitizeInput($_POST['nid']),
        'religion' => sanitizeInput($_POST['religion']),
        'nationality' => sanitizeInput($_POST['nationality'])
    ];

    // Handle Expertise & Interests (comma separated to array)
    $data['resume_data']['expertise'] = array_map('trim', explode(',', $_POST['expertise']));
    $data['resume_data']['interests'] = array_map('trim', explode(',', $_POST['interests']));

    if (savePortfolioData($data)) {
        $audit->log("Update Resume Data", "Personal info and expertise updated");
        setFlashMessage('Resume data updated successfully!');
        header('Location: edit-resume-data.php');
        exit;
    } else {
        $audit->log("Update Resume Data Failed", "Save error", "failed");
        setFlashMessage('Error saving resume data', 'error');
    }
}

$personal = $data['resume_data']['personal_info'] ?? [];
$expertiseStr = implode(', ', $data['resume_data']['expertise'] ?? []);
$interestsStr = implode(', ', $data['resume_data']['interests'] ?? []);
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Manage Resume Data</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="editor-card">
        <h3><i class="fas fa-user-circle"></i> Personal Information (For CV)</h3>
        <p style="color:#888; margin-bottom: 20px;">These details appear only on the generated Resume/CV page.</p>
        
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Father's Name</label>
                <input type="text" name="father_name" value="<?php echo htmlspecialchars($personal['father_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Mother's Name</label>
                <input type="text" name="mother_name" value="<?php echo htmlspecialchars($personal['mother_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="text" name="dob" value="<?php echo htmlspecialchars($personal['dob'] ?? ''); ?>" placeholder="e.g. 28-Nov-2003" required>
            </div>
            <div class="form-group">
                <label>National ID (NID)</label>
                <input type="text" name="nid" value="<?php echo htmlspecialchars($personal['nid'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Religion</label>
                <input type="text" name="religion" value="<?php echo htmlspecialchars($personal['religion'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Nationality</label>
                <input type="text" name="nationality" value="<?php echo htmlspecialchars($personal['nationality'] ?? ''); ?>" required>
            </div>
        </div>
    </div>

    <div class="editor-card" style="margin-top: 20px;">
        <h3><i class="fas fa-brain"></i> Expertise & Interests</h3>
        <div class="form-group">
            <label>Expertise (Comma separated)</label>
            <textarea name="expertise" rows="3" placeholder="Creativity, Leadership, Networking..."><?php echo htmlspecialchars($expertiseStr); ?></textarea>
        </div>
        <div class="form-group">
            <label>Interests (Comma separated)</label>
            <textarea name="interests" rows="3" placeholder="Traveling, Books, Tech..."><?php echo htmlspecialchars($interestsStr); ?></textarea>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: right;">
        <button type="submit" class="btn-login" style="width: 250px;"><i class="fas fa-save"></i> Save Resume Data</button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
