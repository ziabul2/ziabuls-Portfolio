<?php
require_once __DIR__ . '/includes/header.php';

// Load portfolio data to show some stats
$json_data = file_get_contents(__DIR__ . '/../data/portfolio.json');
$data = json_decode($json_data, true);

$project_count = count($data['projects_section']['items'] ?? []);
$skill_count = 0;
foreach ($data['skills_section']['categories'] ?? [] as $cat) {
    $skill_count += count($cat['items'] ?? []);
}
?>

<div class="welcome-section" style="margin-bottom: 30px;">
    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h1>
    <p>Manage your portfolio content from here.</p>
</div>

<div class="dashboard-grid">
    <div class="stat-card">
        <h3><i class="fas fa-project-diagram"></i> Projects</h3>
        <p>Currently displaying <strong><?php echo $project_count; ?></strong> projects.</p>
        <div style="margin-top:20px;">
            <a href="edit-projects.php" class="btn-edit">Edit Projects</a>
        </div>
    </div>
    
    <div class="stat-card">
        <h3><i class="fas fa-tools"></i> Skills</h3>
        <p>Total of <strong><?php echo $skill_count; ?></strong> skills listed.</p>
        <div style="margin-top:20px;">
            <a href="edit-skills.php" class="btn-edit">Edit Skills</a>
        </div>
    </div>
    
    <div class="stat-card">
        <h3><i class="fas fa-user-circle"></i> Profile</h3>
        <p>Edit your bio, photo, and social links.</p>
        <div style="margin-top:20px;">
            <a href="edit-profile.php" class="btn-edit">Edit Profile</a>
        </div>
    </div>
    
    <div class="stat-card">
        <h3><i class="fas fa-envelope"></i> Contacts</h3>
        <p>Update your contact info and location.</p>
        <div style="margin-top:20px;">
            <a href="edit-contact.php" class="btn-edit">Edit Contacts</a>
        </div>
    </div>

    <div class="stat-card">
        <h3><i class="fas fa-search"></i> SEO & Site</h3>
        <p>Manage site title, favicon, and CSS links.</p>
        <div style="margin-top:20px;">
            <a href="edit-seo.php" class="btn-edit">Edit SEO</a>
        </div>
    </div>

    <div class="stat-card">
        <h3><i class="fas fa-desktop"></i> Header & Footer</h3>
        <p>Manage site logos, roles, and footer info.</p>
        <div style="margin-top:20px;">
            <a href="edit-site.php" class="btn-edit">Edit UI</a>
        </div>
    </div>

    <div class="stat-card">
        <h3><i class="fas fa-blog"></i> Blog Posts</h3>
        <p>Manage your articles and news updates.</p>
        <div style="margin-top:20px; display: flex; gap: 10px;">
            <a href="manage-blog.php" class="btn-edit">Manage Posts</a>
            <a href="edit-blog-settings.php" class="btn-edit">UI Settings</a>
        </div>
    </div>

    <div class="stat-card">
        <h3><i class="fas fa-save"></i> Backups</h3>
        <p>Manage your portfolio data backups and restore previous versions.</p>
        <div style="margin-top:20px;">
            <a href="manage-backups.php" class="btn-edit">Manage Backups</a>
        </div>
    </div>
</div>

<div class="security-info" style="margin-top: 50px; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 8px;">
    <h3><i class="fas fa-shield-alt"></i> Security Status</h3>
    <ul style="margin-top:10px; list-style:none;">
        <li><i class="fas fa-check-circle" style="color:var(--success-color)"></i> Session is active and secure</li>
        <li><i class="fas fa-check-circle" style="color:var(--success-color)"></i> Config directory is protected via .htaccess</li>
        <li><i class="fas fa-check-circle" style="color:var(--success-color)"></i> Password is stored as a secure bcrypt hash</li>
    </ul>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
