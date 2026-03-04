<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request token. Please try again.', 'error');
        header('Location: edit-seo.php');
        exit;
    }

    // Basic sanitization
    // Handle Global SEO
    $data['seo']['description'] = sanitizeInput($_POST['description'] ?? '');
    $data['seo']['keywords'] = sanitizeInput($_POST['keywords'] ?? '');
    $data['seo']['author'] = sanitizeInput($_POST['author'] ?? '');
    $data['seo']['robots'] = sanitizeInput($_POST['robots'] ?? 'index, follow');
    
    // Handle Social (OG)
    $data['seo']['og_image'] = sanitizeInput($_POST['og_image'] ?? $data['seo']['favicon']); // specific or fallback
    
    // Handle Analytics / Verification
    $rawTags = $_POST['google_analytics'] ?? '';
    $tagsArray = array_map('trim', explode(',', $rawTags));
    $tagsArray = array_filter($tagsArray); // Remove empty
    
    $data['seo']['google_tags'] = $tagsArray;
    $data['seo']['google_analytics'] = !empty($tagsArray) ? $tagsArray[0] : '';
    $data['seo']['search_console'] = sanitizeInput($_POST['search_console'] ?? '');

    // Handle robots.txt
    if (isset($_POST['robots_txt'])) {
        file_put_contents(__DIR__ . '/../robots.txt', $_POST['robots_txt']);
    }
    
    // Handle Stylesheets
    if (isset($_POST['stylesheets']) && is_array($_POST['stylesheets'])) {
        $data['seo']['stylesheets'] = array_map('sanitizeInput', array_filter($_POST['stylesheets']));
    }

    if (savePortfolioData($data)) {
        setFlashMessage('Expert SEO & Robots settings updated!');
        header('Location: edit-seo.php');
        exit;
    } else {
        setFlashMessage('Error saving settings', 'error');
    }
}

$robotsContent = file_exists(__DIR__ . '/../robots.txt') ? file_get_contents(__DIR__ . '/../robots.txt') : "User-agent: *\nDisallow:";
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
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
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
                    <label>Google Tag IDs <span class="tooltip-icon" data-tip="Enter IDs separated by commas (e.g., G-XXXX, GT-YYYY)">?</span></label>
                    <?php 
                    $currentTags = $data['seo']['google_tags'] ?? [$data['seo']['google_analytics'] ?? ''];
                    $tagsString = implode(', ', array_filter($currentTags));
                    ?>
                    <input type="text" name="google_analytics" value="<?php echo htmlspecialchars($tagsString); ?>" placeholder="G-T005YHR72T, GT-WKTM3LHJ">
                    <div style="margin-top: 8px; font-size: 0.75rem; color: #888;">
                        <i class="fas fa-info-circle"></i> Separate multiple IDs with commas.
                    </div>
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

    <!-- STYLESHEETS & ROBOTS -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px; margin-top:30px;">
        <div class="editor-card">
            <h2><i class="fas fa-robot"></i> robots.txt Editor</h2>
            <div class="form-group">
                <label>File Content (robots.txt) <span class="tooltip-icon" data-tip="Manage search engine access and sitemap location">?</span></label>
                <textarea name="robots_txt" style="height:150px; font-family:monospace;"><?php echo htmlspecialchars($robotsContent); ?></textarea>
                <small style="color:#666;">Be careful! Incorrect rules can hide your site from search engines.</small>
            </div>
        </div>

        <div class="editor-card">
            <h2><i class="fas fa-sitemap"></i> Sitemap Status</h2>
            <div style="background:rgba(152,195,121,0.1); padding:15px; border-radius:4px; border:1px solid rgba(152,195,121,0.3);">
                <p style="font-size:0.9rem; color:#98c379; margin-bottom:10px;">
                    <i class="fas fa-check-circle"></i> <strong>Sitemap is Dynamic</strong>
                </p>
                <?php 
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $sitemapUrl = $protocol . $_SERVER['HTTP_HOST'] . str_replace('admin/edit-seo.php', 'sitemap.php', $_SERVER['SCRIPT_NAME']);
                ?>
                <p style="font-size:0.8rem; color:#ccc; word-break:break-all;">
                    <strong>URL:</strong> <a href="<?php echo $sitemapUrl; ?>" target="_blank" style="color:var(--accent-blue);"><?php echo $sitemapUrl; ?></a>
                </p>
                <div style="margin-top:15px; padding-top:15px; border-top:1px solid #444;">
                    <h4 style="font-size:0.85rem; color:#888; margin-bottom:8px;">SEO Best Practice:</h4>
                    <p style="font-size:0.75rem; color:#666; line-height:1.4;">
                        Your sitemap is automatically generated from your projects and blog posts. 
                        We recommend adding the Sitemap URL to your <strong>Google Search Console</strong> for faster indexing.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- STYLESHEETS (Kept from original) -->
    <div class="editor-card" style="margin-top:30px;">
        <h2><i class="fas fa-code"></i> Stylesheets & Resources</h2>
        <div id="stylesheets-container">
            <?php foreach (($data['seo']['stylesheets'] ?? []) as $index => $sheet): ?>
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
