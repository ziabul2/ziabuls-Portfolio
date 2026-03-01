<?php
/**
 * edit-achievement.php - Add/Edit an achievement
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/AchievementManager.php';

$achievementManager = AchievementManager::getInstance();
$id = $_GET['id'] ?? '';
$isEdit = !empty($id);

$achievement = [
    'id' => '',
    'title' => '',
    'short_description' => '',
    'long_description' => '',
    'category' => 'Other',
    'completion_date' => date('Y-m-d'),
    'organization' => '',
    'certificate_image' => '',
    'verification_link' => '',
    'database_subject' => ''
];

if ($isEdit) {
    $existing = $achievementManager->getAchievement($id);
    if ($existing) {
        $achievement = array_merge($achievement, $existing);
    } else {
        setFlashMessage('Achievement not found', 'error');
        header('Location: manage-achievements.php');
        exit;
    }
}

$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic Sanitization
    $title = sanitizeInput($_POST['title'] ?? '');
    $short_desc = sanitizeInput($_POST['short_description'] ?? '');
    // Don't fully sanitize long description to allow basic HTML or safe text
    $long_desc = $_POST['long_description'] ?? ''; 
    $category = sanitizeInput($_POST['category'] ?? 'Other');
    $completion_date = sanitizeInput($_POST['completion_date'] ?? date('Y-m-d'));
    $organization = sanitizeInput($_POST['organization'] ?? '');
    $verification_link = sanitizeInput($_POST['verification_link'] ?? '');
    $database_subject = sanitizeInput($_POST['database_subject'] ?? '');

    // Handle File Upload
    $certificate_image = $achievement['certificate_image']; // keep existing by default
    
    // Check if simple file path text field was used or file upload
    if (!empty($_POST['certificate_image_url'])) {
        $certificate_image = sanitizeInput($_POST['certificate_image_url']);
    }

    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleFileUpload($_FILES['certificate_file']);
        if (is_array($uploadResult) && isset($uploadResult['error'])) {
             setFlashMessage($uploadResult['error'], 'error');
        } else {
             $certificate_image = $uploadResult;
        }
    }

    if (empty($title) || empty($short_desc)) {
        setFlashMessage('Title and Short Description are required.', 'error');
    } else {
        $dataToSave = [
            'title' => $title,
            'short_description' => $short_desc,
            'long_description' => $long_desc,
            'category' => $category,
            'completion_date' => $completion_date,
            'organization' => $organization,
            'certificate_image' => $certificate_image,
            'verification_link' => $verification_link
        ];

        // Optional fields - only include if provided, to keep JSON clean but accessible
        if ($database_subject !== '') {
            $dataToSave['database_subject'] = $database_subject;
        }

        if ($isEdit) {
            $dataToSave['id'] = $id;
        }

        if ($achievementManager->saveAchievement($dataToSave)) {
            setFlashMessage('Achievement saved successfully!');
            header('Location: manage-achievements.php');
            exit;
        } else {
            setFlashMessage('Error saving achievement.', 'error');
        }
    }
    
    // Update local variable for re-rendering form on error
    $achievement = array_merge($achievement, $_POST);
    $achievement['certificate_image'] = $certificate_image;
}

$categories = ['Academic', 'Programming', 'Competition', 'Other'];
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1><?php echo $isEdit ? 'Edit Achievement' : 'Add New Achievement'; ?></h1>
    <a href="manage-achievements.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Achievements</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="editor-card">
    <form method="POST" enctype="multipart/form-data" class="admin-form">
        
        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($achievement['title']); ?>" required>
        </div>

        <div class="form-group" style="display:flex; gap:20px;">
            <div style="flex:1;">
                <label>Category *</label>
                <select name="category" required style="width:100%; padding:10px; background:#222; color:#fff; border:1px solid #444;">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $achievement['category'] === $cat ? 'selected' : ''; ?>>
                            <?php echo $cat; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1;">
                <label>Completion Date *</label>
                <input type="date" name="completion_date" value="<?php echo htmlspecialchars($achievement['completion_date']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Short Description (Preview) *</label>
            <input type="text" name="short_description" value="<?php echo htmlspecialchars($achievement['short_description']); ?>" maxlength="255" required placeholder="A brief summary...">
        </div>

        <div class="form-group">
            <label>Organization / Event Name</label>
            <input type="text" name="organization" value="<?php echo htmlspecialchars($achievement['organization']); ?>" placeholder="e.g. University Name, Hackathon Organizer...">
        </div>

        <div class="form-group">
            <label>Certificate Image</label>
            <div style="display:flex; gap:10px; align-items:center;">
                <input type="file" name="certificate_file" accept="image/*" style="flex:1">
                <span style="color:#888;">OR URL:</span>
                <input type="text" name="certificate_image_url" value="<?php echo htmlspecialchars($achievement['certificate_image']); ?>" style="flex:2" placeholder="e.g. assets/cert1.jpg">
            </div>
            <?php if (!empty($achievement['certificate_image'])): ?>
                <div style="margin-top:10px;">
                    <?php 
                        $imgSrc = $achievement['certificate_image'];
                        if (!str_starts_with($imgSrc, '/') && !str_starts_with($imgSrc, 'http')) {
                            $imgSrc = '../' . $imgSrc;
                        }
                    ?>
                    <img src="<?php echo htmlspecialchars($imgSrc); ?>" style="max-width: 200px; border-radius:4px; border:1px solid #444;">
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Database Subject (Optional)</label>
            <input type="text" name="database_subject" value="<?php echo htmlspecialchars($achievement['database_subject'] ?? ''); ?>" placeholder="Optional field requested...">
            <small style="color:#888; display:block; margin-top:5px;">This field is optional and handles backward compatibility safely.</small>
        </div>

        <div class="form-group">
            <label>Verification Link</label>
            <input type="url" name="verification_link" value="<?php echo htmlspecialchars($achievement['verification_link']); ?>" placeholder="https://...">
        </div>

        <div class="form-group">
            <label>Full Detailed Explanation *</label>
            <textarea name="long_description" rows="10" required><?php echo htmlspecialchars($achievement['long_description']); ?></textarea>
            <small style="color:#888; display:block; margin-top:5px;">You can use HTML tags like &lt;strong&gt;, &lt;em&gt;, &lt;br&gt; or &lt;p&gt; for formatting.</small>
        </div>

        <button type="submit" class="btn-login">Save Achievement</button>
    </form>
</div>

<!-- Ensure form inputs are styled like the rest of the admin panel -->
<style>
.admin-form input[type="text"],
.admin-form input[type="url"],
.admin-form input[type="date"],
.admin-form textarea,
.admin-form input[type="file"] {
    width: 100%;
    padding: 10px;
    background: #222;
    border: 1px solid #444;
    color: white;
    border-radius: 4px;
    margin-top: 5px;
}
.admin-form label {
    font-weight: bold;
    color: #c778dd;
    display: block;
    margin-bottom: 5px;
}
.form-group {
    margin-bottom: 20px;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
