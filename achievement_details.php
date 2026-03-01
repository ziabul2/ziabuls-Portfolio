<?php
/**
 * achievement_details.php - Fetches individual achievement from JSON
 */
require_once __DIR__ . '/helpers/data_loader.php';
$data = loadPortfolioData();
$achievementId = $_GET['id'] ?? '';

$achievement = null;
if ($achievementId) {
    try {
        require_once __DIR__ . '/helpers/AchievementManager.php';
        $achievementManager = AchievementManager::getInstance();
        $achievement = $achievementManager->getAchievement($achievementId);
    } catch (Exception $e) {
        error_log('Error fetching achievement: ' . $e->getMessage());
    }
}

// 404 if not found
if (!$achievement) {
    header('HTTP/1.0 404 Not Found');
    echo '404 - Achievement not found';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/includes/head.php'; ?>
<body style="padding-top: 100px;">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="container" style="max-width: 900px; margin: 0 auto;">
    <!-- Back Button -->
    <div style="margin-bottom: 30px;">
        <a href="achievements.php" style="color: #c778dd; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-arrow-left"></i> Back to Achievements
        </a>
    </div>

    <!-- Header -->
    <article style="margin-bottom: 50px;">
        <div style="margin-bottom: 20px;">
            <span style="color: #abb2bf; font-size: 14px;">
                <i class="fas fa-calendar-alt"></i> 
                <?php echo date('F j, Y', strtotime($achievement['completion_date'] ?? 'now')); ?>
                
                <?php if (!empty($achievement['category'])): ?>
                    <span style="margin: 0 10px;">|</span>
                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($achievement['category']); ?>
                <?php endif; ?>

                <?php if (!empty($achievement['organization'])): ?>
                    <span style="margin: 0 10px;">|</span>
                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($achievement['organization']); ?>
                <?php endif; ?>
            </span>
        </div>
        
        <h1 style="font-size: 42px; margin-bottom: 20px; color: #fff;">
            <?php echo htmlspecialchars($achievement['title']); ?>
        </h1>

        <!-- Optional Subject Field -->
        <?php if (!empty($achievement['database_subject'])): ?>
            <div style="background: rgba(199, 120, 221, 0.1); border-left: 4px solid #c778dd; padding: 10px 15px; margin-bottom: 25px; color: #fff;">
                <strong>Subject/Topic:</strong> <?php echo htmlspecialchars($achievement['database_subject']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($achievement['short_description'])): ?>
            <p style="font-size: 18px; color: #abb2bf; margin-bottom: 30px;">
                <em><?php echo htmlspecialchars($achievement['short_description']); ?></em>
            </p>
        <?php endif; ?>

        <!-- Certificate Image -->
        <?php if (!empty($achievement['certificate_image'])): ?>
            <div style="margin-bottom: 40px; text-align: center;">
                <img src="<?php echo htmlspecialchars($achievement['certificate_image']); ?>" 
                     alt="<?php echo htmlspecialchars($achievement['title']); ?>"
                     style="max-width: 100%; border-radius: 8px; border: 1px solid #1e2d3d; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
            </div>
        <?php endif; ?>

        <!-- Content -->
        <?php if (!empty($achievement['long_description'])): ?>
            <div class="post-content" style="line-height: 1.8; color: #abb2bf;">
                <?php 
                // Convert newlines to paragraphs/br if it's plain text, 
                // but if it's rich text (HTML), it will render cleanly. 
                // Simple assumption: if it contains <p> or <br>, it's HTML, else inject nl2br.
                $desc = $achievement['long_description'];
                if (strpos($desc, '<') === false) {
                    echo nl2br(htmlspecialchars($desc));
                } else {
                    echo $desc;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Verification Link -->
        <?php if (!empty($achievement['verification_link'])): ?>
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #1e2d3d;">
                <a href="<?php echo htmlspecialchars($achievement['verification_link']); ?>" target="_blank" class="btn" style="display: inline-block;">
                    <i class="fas fa-external-link-alt"></i> Verify Achievement
                </a>
            </div>
        <?php endif; ?>
        
    </article>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="js/script.js"></script>

<style>
.post-content h1, .post-content h2, .post-content h3 {
    color: #fff;
    margin-top: 30px;
    margin-bottom: 15px;
}
.post-content h1 { font-size: 32px; }
.post-content h2 { font-size: 28px; }
.post-content h3 { font-size: 24px; }
.post-content p {
    margin-bottom: 20px;
}
.post-content a {
    color: #c778dd;
    text-decoration: none;
}
.post-content a:hover {
    text-decoration: underline;
}
</style>
</body>
</html>
