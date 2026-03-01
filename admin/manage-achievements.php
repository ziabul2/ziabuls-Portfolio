<?php
/**
 * manage-achievements.php - Admin panel list view for achievements
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/AchievementManager.php';

$achievementManager = AchievementManager::getInstance();
$flash = getFlashMessage();

// Handle deletion
if (isset($_GET['delete'])) {
    $achievementId = $_GET['delete'];
    
    if ($achievementManager->deleteAchievement($achievementId)) {
        setFlashMessage('Achievement deleted successfully!');
        header('Location: manage-achievements.php');
        exit;
    } else {
        setFlashMessage('Error deleting achievement', 'error');
    }
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Manage Achievements</h1>
    <div style="display:flex; gap:10px;">
        <a href="edit-achievement.php" class="btn-login" style="width:auto; padding: 10px 20px;">+ Add New Achievement</a>
        <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<?php 
// Handle Search
$search = $_GET['search'] ?? '';
$achievements = $achievementManager->getAchievements();

if ($search) {
    $search = strtolower(trim($search));
    $achievements = array_filter($achievements, function($a) use ($search) {
        return (
            strpos(strtolower($a['id']), $search) !== false || 
            strpos(strtolower($a['title']), $search) !== false ||
            strpos(strtolower($a['organization'] ?? ''), $search) !== false ||
            strpos(strtolower($a['category'] ?? ''), $search) !== false
        );
    });
}
?>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="editor-card">
    <form method="GET" style="display:flex; gap:10px; margin-bottom:20px;">
        <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search by ID, Title, Organization, Category..." style="flex:1; background:#222; border:1px solid #444; padding:10px; color:white;">
        <button type="submit" class="btn-login" style="width:auto;"><i class="fas fa-search"></i> Search</button>
        <?php if($search): ?>
            <a href="manage-achievements.php" class="btn-remove" style="position:static; padding:10px 20px;">Clear</a>
        <?php endif; ?>
    </form>

    <table style="width:100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="border-bottom: 1px solid #444; text-align: left;">
                <th style="padding: 15px;">Image</th>
                <th style="padding: 15px; width:120px;">ID / Cat</th>
                <th style="padding: 15px;">Title & Org</th>
                <th style="padding: 15px;">Completed On</th>
                <th style="padding: 15px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($achievements)): ?>
                <tr>
                    <td colspan="5" style="padding: 30px; text-align: center; color: #888;">No achievements found. <a href="edit-achievement.php">Add one now!</a></td>
                </tr>
            <?php else: ?>
                <?php foreach ($achievements as $achievement): ?>
                    <tr style="border-bottom: 1px solid #222;">
                        <td style="padding: 15px;">
                            <?php 
                                $imgSrc = $achievement['certificate_image'] ?? '';
                                if (!empty($imgSrc) && !str_starts_with($imgSrc, '/') && !str_starts_with($imgSrc, 'http')) {
                                    $imgSrc = '../' . $imgSrc;
                                }
                            ?>
                            <?php if ($imgSrc): ?>
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #333; border-radius: 4px; display:flex; align-items:center; justify-content:center; color:#666;">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; color: #888;">
                            <span style="font-family: monospace; font-size:12px; display:block; margin-bottom:5px;"><?php echo htmlspecialchars($achievement['id']); ?></span>
                            <?php if(!empty($achievement['category'])): ?>
                                <span class="status-badge" style="background:#334; color:#aab;"><?php echo htmlspecialchars($achievement['category']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;">
                            <strong><?php echo htmlspecialchars($achievement['title']); ?></strong>
                            <?php if (!empty($achievement['organization'])): ?>
                                <br><small style="color: #888;"><i class="fas fa-building"></i> <?php echo htmlspecialchars($achievement['organization']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; text-wrap: nowrap;"><?php 
                            echo date('M d, Y', strtotime($achievement['completion_date'] ?? 'now')); 
                        ?></td>
                        <td style="padding: 15px; text-align: right; text-wrap: nowrap;">
                            <a href="edit-achievement.php?id=<?php echo $achievement['id']; ?>" class="btn-edit" style="margin-right: 10px;">Edit</a>
                            <a href="manage-achievements.php?delete=<?php echo $achievement['id']; ?>" class="btn-remove" style="position:static;" onclick="return confirm('Are you sure you want to delete this achievement?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.status-badge {
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
