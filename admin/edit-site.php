<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$data = getPortfolioData();
$flash = getFlashMessage();
$assets = getAvailableAssets();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Header
    $data['footer']['logo_text'] = sanitizeInput($_POST['header_logo'] ?? '');
    $data['footer']['role'] = sanitizeInput($_POST['header_role'] ?? '');
    
    // Handle Footer
    $data['footer']['email'] = sanitizeInput($_POST['footer_email'] ?? '');
    $data['footer']['media_title'] = sanitizeInput($_POST['footer_media_title'] ?? '');
    $data['footer']['copyright_text'] = sanitizeInput($_POST['footer_copyright'] ?? '');

    // Handle Site General (from SEO)
    $data['seo']['title'] = sanitizeInput($_POST['site_title'] ?? '');
    $data['seo']['favicon'] = sanitizeInput($_POST['site_favicon'] ?? '');

    // Handle Admin UI Settings
    if (!isset($data['admin_settings'])) $data['admin_settings'] = [];
    $data['admin_settings']['title'] = sanitizeInput($_POST['admin_title'] ?? 'Admin Dashboard');
    $data['admin_settings']['header_text'] = sanitizeInput($_POST['admin_header_text'] ?? 'admin-panel');
    $data['admin_settings']['footer_text'] = sanitizeInput($_POST['admin_footer_text'] ?? 'ZIMBABU Admin Panel');
    $data['admin_settings']['favicon'] = sanitizeInput($_POST['admin_favicon'] ?? '');

    // Handle Theme Personalization
    if (!isset($data['theme'])) $data['theme'] = [];
    $data['theme']['primary_color'] = sanitizeInput($_POST['primary_color'] ?? '#c778dd');
    $data['theme']['accent_color'] = sanitizeInput($_POST['accent_color'] ?? '#61afef');

    if (savePortfolioData($data)) {
        setFlashMessage('Site settings & Theme updated successfully!');
        header('Location: edit-site.php');
        exit;
    } else {
        setFlashMessage('Error saving site settings', 'error');
    }
}
?>

<style>
/* Simple CSS Tooltip */
.tooltip-icon {
    display: inline-block;
    width: 16px; 
    height: 16px; 
    background: #444; 
    color: #fff; 
    border-radius: 50%; 
    text-align: center; 
    line-height: 16px; 
    font-size: 10px; 
    cursor: help;
    margin-left: 5px;
    position: relative;
}
.tooltip-icon:hover::after {
    content: attr(data-tip);
    position: absolute;
    bottom: 120%;
    left: 50%;
    transform: translateX(-50%);
    background: #000;
    color: #fff;
    padding: 5px 10px;
    border-radius: 4px;
    white-space: nowrap;
    z-index: 100;
    font-size: 11px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.5);
}
</style>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Edit Site UI (Header & Footer)</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<form method="POST">
    <div class="editor-card" style="border-left: 4px solid var(--accent-color);">
        <h2>Admin Panel Configuration</h2>
        <div class="form-group">
            <label for="admin_title">Admin Page Title</label>
            <input type="text" id="admin_title" name="admin_title" value="<?php echo htmlspecialchars($data['admin_settings']['title'] ?? 'Admin Dashboard'); ?>" required>
        </div>
        <div class="form-group">
            <label for="admin_header_text">Header Text (Logo)</label>
            <input type="text" id="admin_header_text" name="admin_header_text" value="<?php echo htmlspecialchars($data['admin_settings']['header_text'] ?? 'admin-panel'); ?>" required>
        </div>
        <div class="form-group">
            <label for="admin_footer_text">Footer Copyright Text</label>
            <input type="text" id="admin_footer_text" name="admin_footer_text" value="<?php echo htmlspecialchars($data['admin_settings']['footer_text'] ?? 'ZIMBABU Admin Panel'); ?>" required>
        </div>
        <div class="form-group">
            <label for="admin_favicon">Admin Favicon</label>
            <div style="display:flex; gap:10px;">
                <input type="text" id="admin_favicon" name="admin_favicon" value="<?php echo htmlspecialchars($data['admin_settings']['favicon'] ?? ''); ?>" placeholder="Same as site? or different...">
                <button type="button" class="btn-edit" style="width:auto; white-space:nowrap;" onclick="openMediaPicker('admin_favicon', 'admin_fav_preview')">Select Icon</button>
            </div>
            <div style="margin-top:10px;">
               <img id="admin_fav_preview" src="../<?php echo htmlspecialchars($data['admin_settings']['favicon'] ?? ''); ?>" style="width:32px; height:32px; object-fit:contain; border:1px solid #444; background:#fff;">
            </div>
        </div>
    </div>

    </div>

    <div class="editor-card" style="border-left: 4px solid #c778dd;">
        <h2><i class="fas fa-palette"></i> Theme Personalization</h2>
        <p style="color:#888; font-size:0.9em; margin-bottom:15px;">Customize the look and feel of your portfolio.</p>
        
        <div style="display:flex; gap:30px;">
            <div class="form-group">
                <label>Primary Brand Color <span class="tooltip-icon" data-tip="Main color for headings, buttons, lines (#c778dd)">?</span></label>
                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="color" name="primary_color" value="<?php echo htmlspecialchars($data['theme']['primary_color'] ?? '#c778dd'); ?>" style="width:50px; height:40px; padding:0; border:none;">
                    <input type="text" value="<?php echo htmlspecialchars($data['theme']['primary_color'] ?? '#c778dd'); ?>" readonly style="width:100px; padding:5px; background:#111; border:1px solid #444; color:#fff;">
                </div>
            </div>
            
             <div class="form-group">
                <label>Accent / Link Color <span class="tooltip-icon" data-tip="Secondary color for links and highlights (#61afef)">?</span></label>
                 <div style="display:flex; align-items:center; gap:10px;">
                    <input type="color" name="accent_color" value="<?php echo htmlspecialchars($data['theme']['accent_color'] ?? '#61afef'); ?>" style="width:50px; height:40px; padding:0; border:none;">
                    <input type="text" value="<?php echo htmlspecialchars($data['theme']['accent_color'] ?? '#61afef'); ?>" readonly style="width:100px; padding:5px; background:#111; border:1px solid #444; color:#fff;">
                </div>
            </div>
        </div>
    </div>

    <div class="editor-card">
        <h2>General Site Settings</h2>
        <div class="form-group">
            <label for="site_title">Browser Tab Title <span class="tooltip-icon" data-tip="Shown in Google Search and Browser Tabs">?</span></label>
            <input type="text" id="site_title" name="site_title" value="<?php echo htmlspecialchars($data['seo']['title'] ?? ''); ?>" required placeholder="e.g. My Portfolio">
        </div>
        <div class="form-group">
            <label for="site_favicon">Favicon / Site Icon <span class="tooltip-icon" data-tip="Small icon shown next to title (32x32px)">?</span></label>
            <div style="display:flex; gap:10px;">
                <input type="text" id="site_favicon" name="site_favicon" value="<?php echo htmlspecialchars($data['seo']['favicon'] ?? ''); ?>" required placeholder="assets/icon.png">
                <button type="button" class="btn-edit" style="width:auto; white-space:nowrap;" onclick="openMediaPicker('site_favicon', 'favicon_preview')">Select Icon</button>
            </div>
            <div style="margin-top:10px;">
               <img id="favicon_preview" src="../<?php echo htmlspecialchars($data['seo']['favicon'] ?? ''); ?>" style="width:32px; height:32px; object-fit:contain; border:1px solid #444; background:#fff;">
            </div>
        </div>
    </div>

    <div class="editor-card">
        <h2>Header Configuration</h2>
        <div class="form-group">
            <label for="header_logo">Logo Text</label>
            <input type="text" id="header_logo" name="header_logo" value="<?php echo htmlspecialchars($data['footer']['logo_text']); ?>" required>
        </div>
        <div class="form-group">
            <label for="header_role">Role Display (Sub-logo)</label>
            <input type="text" id="header_role" name="header_role" value="<?php echo htmlspecialchars($data['footer']['role']); ?>" required>
        </div>
    </div>

    <div class="editor-card">
        <h2>Footer Configuration</h2>
        <div class="form-group">
            <label for="footer_email">Footer Email</label>
            <input type="email" id="footer_email" name="footer_email" value="<?php echo htmlspecialchars($data['footer']['email']); ?>" required>
        </div>
        <div class="form-group">
            <label for="footer_media_title">Social Media Title</label>
            <input type="text" id="footer_media_title" name="footer_media_title" value="<?php echo htmlspecialchars($data['footer']['media_title']); ?>" required>
        </div>
        <div class="form-group">
            <label for="footer_copyright">Copyright Text</label>
            <input type="text" id="footer_copyright" name="footer_copyright" value="<?php echo htmlspecialchars($data['footer']['copyright_text']); ?>" required>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: right;">
        <button type="submit" class="btn-login" style="width: 200px;">Save Site Settings</button>
    </div>
</form>

<?php 
require_once __DIR__ . '/includes/media-picker.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
