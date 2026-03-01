<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization
    // Handle Global SEO
    $data['seo']['description'] = sanitizeInput($_POST['description'] ?? '');
    $data['seo']['keywords'] = sanitizeInput($_POST['keywords'] ?? '');
    $data['seo']['author'] = sanitizeInput($_POST['author'] ?? '');
    $data['seo']['robots'] = sanitizeInput($_POST['robots'] ?? 'index, follow');
    
    // Handle Social (OG)
    $data['seo']['og_image'] = sanitizeInput($_POST['og_image'] ?? $data['seo']['favicon']); // specific or fallback
    
    // Handle Analytics / Verification
    $data['seo']['google_analytics'] = sanitizeInput($_POST['google_analytics'] ?? '');
    $data['seo']['search_console'] = sanitizeInput($_POST['search_console'] ?? '');
    
    // Handle Stylesheets
    if (isset($_POST['stylesheets']) && is_array($_POST['stylesheets'])) {
        $data['seo']['stylesheets'] = array_map('sanitizeInput', array_filter($_POST['stylesheets']));
    }

    if (savePortfolioData($data)) {
        setFlashMessage('Expert SEO settings updated!');
        header('Location: edit-seo.php');
        exit;
    } else {
        setFlashMessage('Error saving settings', 'error');
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Expert SEO Configuration</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<style>
/* Simple CSS Tooltip (Same as edit-site) */
.tooltip-icon {
    display: inline-block; width: 16px; height: 16px; background: #444; color: #fff; 
    border-radius: 50%; text-align: center; line-height: 16px; font-size: 10px; 
    cursor: help; margin-left: 5px; position: relative;
}
.tooltip-icon:hover::after {
    content: attr(data-tip); position: absolute; bottom: 120%; left: 50%; transform: translateX(-50%);
    background: #000; color: #fff; padding: 5px 10px; border-radius: 4px; white-space: nowrap; z-index: 100; font-size: 11px;
}
</style>

<form method="POST">
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
        
        <!-- GLOBAL META -->
        <div class="editor-card">
            <h2><i class="fas fa-globe"></i> Global Metadata</h2>
            <div class="form-group">
                <label>Default Meta Description <span class="tooltip-icon" data-tip="150-160 characters summary of your site">?</span></label>
                <textarea name="description" style="height:100px;"><?php echo htmlspecialchars($data['seo']['description'] ?? ''); ?></textarea>
                <small style="color:#666;">Critically important for search result click-through rates.</small>
            </div>
            <div class="form-group">
                <label>Global Keywords <span class="tooltip-icon" data-tip="Comma separated terms users might search for">?</span></label>
                <input type="text" name="keywords" value="<?php echo htmlspecialchars($data['seo']['keywords'] ?? ''); ?>" placeholder="portfolio, web developer, php">
            </div>
            <div class="form-group">
                <label>Author / Owner Name</label>
                <input type="text" name="author" value="<?php echo htmlspecialchars($data['seo']['author'] ?? 'Ziabul Islam'); ?>">
            </div>
            <div class="form-group">
                <label>Robots Meta Tag <span class="tooltip-icon" data-tip="Control search engine crawling behavior">?</span></label>
                <select name="robots" style="width:100%; padding:10px; background:#111; color:white; border:1px solid #444;">
                    <option value="index, follow" <?php echo ($data['seo']['robots'] ?? '') === 'index, follow' ? 'selected' : ''; ?>>index, follow (Recommended)</option>
                    <option value="noindex, follow" <?php echo ($data['seo']['robots'] ?? '') === 'noindex, follow' ? 'selected' : ''; ?>>noindex, follow</option>
                    <option value="index, nofollow" <?php echo ($data['seo']['robots'] ?? '') === 'index, nofollow' ? 'selected' : ''; ?>>index, nofollow</option>
                    <option value="noindex, nofollow" <?php echo ($data['seo']['robots'] ?? '') === 'noindex, nofollow' ? 'selected' : ''; ?>>noindex, nofollow</option>
                </select>
            </div>
        </div>

        <!-- ANALYTICS & SOCIAL -->
        <div>
            <div class="editor-card">
                <h2><i class="fas fa-chart-line"></i> Analytics & Verification</h2>
                
                <div style="background:rgba(0,180,255,0.1); padding:15px; border-radius:4px; margin-bottom:20px; border-left:3px solid #00b4ff;">
                    <h4 style="margin-bottom:5px; color:#00b4ff;"><i class="fas fa-info-circle"></i> Quick Tip</h4>
                    <p style="font-size:0.85em; color:#ccc;">
                        To get your <strong>Google Analytics ID</strong>:<br>
                        1. Go to analytics.google.com<br>
                        2. Create property -> Data Streams -> Web<br>
                        3. Copy the "Measurement ID" (starts with G-).
                    </p>
                </div>

                <div class="form-group">
                    <label>Google Analytics ID <span class="tooltip-icon" data-tip="Format: G-XXXXXXXXXX">?</span></label>
                    <input type="text" name="google_analytics" value="<?php echo htmlspecialchars($data['seo']['google_analytics'] ?? ''); ?>" placeholder="G-MEASUREMENT_ID">
                </div>
                <div class="form-group">
                    <label>Google Search Console <span class="tooltip-icon" data-tip="HTML Tag verification code content only">?</span></label>
                    <input type="text" name="search_console" value="<?php echo htmlspecialchars($data['seo']['search_console'] ?? ''); ?>" placeholder="Verification string only">
                </div>
            </div>

            <div class="editor-card" style="margin-top:20px;">
                <h2><i class="fas fa-share-alt"></i> Social Media (Open Graph)</h2>
                <div class="form-group">
                    <label>Default Social Share Image <span class="tooltip-icon" data-tip="Image shown when sharing links on Facebook/Twitter">?</span></label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" id="og_image" name="og_image" value="<?php echo htmlspecialchars($data['seo']['og_image'] ?? ''); ?>" placeholder="assets/social-card.png">
                        <button type="button" class="btn-edit" onclick="openMediaPicker('og_image', 'og_preview')">Select</button>
                    </div>
                     <div style="margin-top:10px;">
                        <img id="og_preview" src="../<?php echo htmlspecialchars($data['seo']['og_image'] ?? ''); ?>" style="width:100%; height:150px; object-fit:cover; border:1px solid #444;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STYLESHEETS (Kept from original) -->
    <div class="editor-card" style="margin-top:30px;">
        <h2><i class="fas fa-code"></i> Stylesheets & Resources</h2>
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
            <button type="submit" class="btn-login" style="width:200px;">Save Expert Settings</button>
        </div>
    </div>
</form>

<?php 
require_once __DIR__ . '/includes/media-picker.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
