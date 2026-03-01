<?php
/**
 * achievements.php - Fetches achievements from JSON database
 */
require_once __DIR__ . '/helpers/data_loader.php';
$data = loadPortfolioData();

require_once __DIR__ . '/helpers/AchievementManager.php';
$achievementManager = AchievementManager::getInstance();

$category = $_GET['category'] ?? '';
$filters = $category ? ['category' => $category] : [];

$achievements_list = $achievementManager->getAchievements($filters);

// Extract all categories for the filter buttons
$all_achievements_for_cats = $achievementManager->getAchievements();
$categories = [];
foreach ($all_achievements_for_cats as $ach) {
    if (!empty($ach['category']) && !in_array($ach['category'], $categories)) {
        $categories[] = $ach['category'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/includes/head.php'; ?>
<body style="padding-top: 100px;">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="container">
    <div class="section-header" style="margin-bottom: 30px;">
        <h1><span>#</span>Achievements</h1>
        <div class="section-line"></div>
    </div>

    <!-- Category Filters -->
    <?php if (!empty($categories)): ?>
    <div style="margin-bottom: 40px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="achievements.php" class="btn btn-sm <?php echo empty($category) ? 'active-filter' : ''; ?>" style="<?php echo empty($category) ? 'background-color: var(--primary-color); color: #fff;' : ''; ?>">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="achievements.php?category=<?php echo urlencode($cat); ?>" class="btn btn-sm" style="<?php echo $category === $cat ? 'background-color: var(--primary-color); color: #fff;' : ''; ?>">
                <?php echo htmlspecialchars($cat); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="project-grid">
        <?php if (empty($achievements_list)): ?>
            <p style="color: #666; font-style: italic;">No achievements found.</p>
        <?php else: ?>
            <?php foreach ($achievements_list as $achievement): ?>
            <div class="project-card">
                <?php if(!empty($achievement['certificate_image'])): ?>
                <div class="project-img">
                    <img src="<?php echo htmlspecialchars($achievement['certificate_image']); ?>" alt="<?php echo htmlspecialchars($achievement['title']); ?>">
                </div>
                <?php endif; ?>
                <div class="project-tech">
                    <?php echo date('M d, Y', strtotime($achievement['completion_date'] ?? 'now')); ?>
                    <?php if(!empty($achievement['organization'])): ?>
                        • <?php echo htmlspecialchars($achievement['organization']); ?>
                    <?php endif; ?>
                </div>
                <div class="project-content">
                    <h3><?php echo htmlspecialchars($achievement['title']); ?></h3>
                    <p><?php echo htmlspecialchars($achievement['short_description']); ?></p>
                    <a href="achievement_details.php?id=<?php echo urlencode($achievement['id']); ?>" class="btn btn-sm">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="js/script.js"></script>
</body>
</html>
