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
    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_data']['display_name'] ?? $_SESSION['admin_data']['username'] ?? 'Admin'); ?>!</h1>
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
        <h3><i class="fas fa-images"></i> Media & Assets</h3>
        <p>View, upload, and delete site images and files.</p>
        <div style="margin-top:20px;">
            <a href="manage-assets.php" class="btn-edit">Manage Assets</a>
        </div>
    </div>

    <div class="stat-card">
        <h3><i class="fas fa-award"></i> Achievements</h3>
        <p>Manage your milestones, certificates, and awards.</p>
        <div style="margin-top:20px; display: flex; gap: 10px; flex-wrap:wrap;">
            <a href="manage-achievements.php" class="btn-edit">Manage</a>
            <a href="edit-achievement.php" class="btn-edit">Add New</a>
            <a href="edit-achievements-settings.php" class="btn-edit">UI Settings</a>
        </div>
    </div>

    <div class="stat-card">
        <h3><i class="fas fa-save"></i> Backups</h3>
        <p>Manage your portfolio data backups and restore previous versions.</p>
        <div style="margin-top:20px;">
            <a href="manage-backups.php" class="btn-edit">Manage Backups</a>
        </div>
    </div>

    <div class="stat-card">
        <h3><i class="fas fa-chart-line"></i> Analytics</h3>
        <p>CEO Power Tools: View page-level stats, device reach, and traffic charts.</p>
        <div style="margin-top:20px;">
            <a href="analytics.php" class="btn-edit">View Stats</a>
        </div>
    </div>

    <div class="stat-card">
        <h3><i class="fas fa-file-pdf"></i> Resume Data</h3>
        <p>Edit personal info, education, and references for your CV.</p>
        <div style="margin-top:20px; display: flex; gap: 10px;">
            <a href="edit-resume-data.php" class="btn-edit">Edit Data</a>
            <a href="../resume.php" target="_blank" class="btn-edit">View CV</a>
        </div>
    </div>

    <?php 
    require_once __DIR__ . '/../helpers/LeadManager.php';
    $lm = new LeadManager();
    $unreadLeads = $lm->getUnreadCount();
    ?>
    <div class="stat-card" style="position:relative;">
        <h3><i class="fas fa-inbox"></i> Leads / Inbox</h3>
        <p>View and manage client inquiries from your site.</p>
        <?php if($unreadLeads > 0): ?>
            <span style="position:absolute; top:15px; right:15px; background: #e06c75; color:white; padding: 2px 8px; border-radius:10px; font-size:0.7rem; font-weight:bold;"><?php echo $unreadLeads; ?> NEW</span>
        <?php endif; ?>
        <div style="margin-top:20px;">
            <a href="manage-leads.php" class="btn-edit">Open Inbox</a>
        </div>
    </div>

    <div class="stat-card">
        <h3><i class="fas fa-quote-left"></i> Testimonials</h3>
        <p>Manage client feedback and show social proof on your site.</p>
        <div style="margin-top:20px;">
            <a href="manage-testimonials.php" class="btn-edit">Manage</a>
        </div>
    </div>

    <div class="stat-card">
        <h3><i class="fas fa-layer-group"></i> Home Sections</h3>
        <p>Manage order, visibility, and dynamic content of homepage sections.</p>
        <div style="margin-top:20px;">
            <a href="manage-sections.php" class="btn-edit">Manage Sections</a>
        </div>
    </div>

    <div class="stat-card">
        <h3><i class="fas fa-shield-alt"></i> Security</h3>
        <p>View administrative audit logs and activity history.</p>
        <div style="margin-top:20px;">
            <a href="audit-logs.php" class="btn-edit">View Logs</a>
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

<?php 
require_once __DIR__ . '/../helpers/AuditLogger.php';
$recentAudit = (new AuditLogger())->getLogs();
$recentAudit = array_slice($recentAudit, 0, 5);
?>
<div class="editor-card" style="margin-top: 40px;">
    <h3><i class="fas fa-history"></i> Recent Admin Activity</h3>
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9rem;">
        <thead>
            <tr style="text-align: left; border-bottom: 1px solid #444;">
                <th style="padding: 10px;">Time</th>
                <th style="padding: 10px;">User</th>
                <th style="padding: 10px;">Action</th>
                <th style="padding: 10px;">Device</th>
                <th style="padding: 10px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentAudit as $log): ?>
                <tr style="border-bottom: 1px solid #222;">
                    <td style="padding: 10px; color: #888; white-space: nowrap;"><?php echo date('h:i A', strtotime($log['timestamp'])); ?></td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($log['admin'] ?? 'System'); ?></td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($log['action']); ?></td>
                    <td style="padding: 10px; font-size: 0.75rem; color: #777;">
                        <div><?php echo AuditLogger::parseUserAgent($log['ua'] ?? ''); ?></div>
                        <div style="font-size: 0.65rem; color: #555; margin-top: 2px;">
                            <i class="fas fa-network-wired"></i> <?php echo AuditLogger::getNetworkInfo($log['ip'] ?? ''); ?>
                        </div>
                    </td>
                    <td style="padding: 10px;">
                        <span style="color: <?php echo ($log['status'] ?? 'success') === 'success' ? 'var(--accent-green)' : 'var(--error-color)'; ?>;">
                            <?php echo htmlspecialchars($log['status'] ?? 'success'); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div style="margin-top: 15px; text-align: right;">
        <a href="audit-logs.php" style="color: var(--accent-color); text-decoration: none; font-size: 0.8rem;">View Full History -></a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
