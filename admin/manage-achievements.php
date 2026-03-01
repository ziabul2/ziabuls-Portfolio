<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../helpers/AchievementManager.php';
require_once __DIR__ . '/includes/functions.php';

$achievementManager = new AchievementManager();
$flash = getFlashMessage();

// Handle Delete
if (isset($_GET['delete'])) {
    if ($achievementManager->deleteAchievement($_GET['delete'])) {
        setFlashMessage('Achievement deleted successfully!');
    } else {
        setFlashMessage('Failed to delete achievement.', 'error');
    }
    header('Location: manage-achievements.php');
    exit;
}

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Get Filtered Achievements
$achievements = $achievementManager->getAchievements($category);

if ($search) {
    $search = strtolower($search);
    $achievements = array_filter($achievements, function($item) use ($search) {
        return strpos(strtolower($item['title']), $search) !== false || 
               strpos(strtolower($item['organization']), $search) !== false;
    });
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Manage Achievements</h1>
    <div style="display:flex; gap:10px;">
        <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <a href="edit-achievement.php" class="btn-add"><i class="fas fa-plus"></i> Add New Achievement</a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="editor-card" style="margin-bottom: 30px;">
    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 250px;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title or organization..." style="width: 100%; padding: 10px; background: #222; border: 1px solid #444; border-radius: 4px; color: white;">
        </div>
        <div>
            <select name="category" style="padding: 10px; background: #222; border: 1px solid #444; border-radius: 4px; color: white;">
                <option value="">All Categories</option>
                <?php 
                $categories = ['Academic', 'Programming', 'Competition', 'Other'];
                foreach($categories as $cat) {
                    $selected = $category === $cat ? 'selected' : '';
                    echo "<option value=\"$cat\" $selected>$cat</option>";
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn-edit"><i class="fas fa-search"></i> Filter</button>
        <?php if($search || $category): ?>
            <a href="manage-achievements.php" class="btn-remove" style="position:static;"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- List -->
<div class="editor-card">
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid #444; text-align: left;">
                <th style="padding: 15px;">Image</th>
                <th style="padding: 15px;">Title & Organization</th>
                <th style="padding: 15px;">Category</th>
                <th style="padding: 15px;">Date</th>
                <th style="padding: 15px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($achievements)): ?>
                <tr>
                    <td colspan="5" style="padding: 30px; text-align: center; color: #888;">No achievements found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($achievements as $item): ?>
                    <tr style="border-bottom: 1px solid #222;">
                        <td style="padding: 15px; width: 80px;">
                            <?php if(!empty($item['certificate_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($item['certificate_image']); ?>" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">
                            <?php else: ?>
                                <div style="width:50px; height:50px; background:#333; border-radius:4px; display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-image" style="color:#666;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;">
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                            <div style="color:#888; font-size:0.9em; margin-top:5px;"><i class="fas fa-building"></i> <?php echo htmlspecialchars($item['organization']); ?></div>
                        </td>
                        <td style="padding: 15px;">
                            <span style="background:var(--accent-color); color:white; padding:4px 10px; border-radius:4px; font-size:0.8em;"><?php echo htmlspecialchars($item['category']); ?></span>
                        </td>
                        <td style="padding: 15px; color:#888;">
                            <?php echo date('M Y', strtotime($item['completion_date'])); ?>
                        </td>
                        <td style="padding: 15px; text-align: right;">
                            <a href="../achievement_details.php?id=<?php echo urlencode($item['id']); ?>" target="_blank" class="btn-edit" style="margin-right:5px;"><i class="fas fa-eye"></i></a>
                            <a href="edit-achievement.php?id=<?php echo urlencode($item['id']); ?>" class="btn-edit" style="margin-right:5px;"><i class="fas fa-edit"></i></a>
                            <a href="manage-achievements.php?delete=<?php echo urlencode($item['id']); ?>" class="btn-remove" style="position:static;" onclick="return confirm('Are you sure you want to delete this achievement?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
