<?php
require_once __DIR__ . '/helpers/data_loader.php';
require_once __DIR__ . '/helpers/AchievementManager.php';

$data = loadPortfolioData();
$id = $_GET['id'] ?? '';
$achievementManager = new AchievementManager();
$item = $achievementManager->getAchievement($id);

if (!$item) {
    require_once __DIR__ . '/includes/head.php';
    require_once __DIR__ . '/includes/navbar.php';
    echo "<div class='container' style='padding: 100px 0; text-align:center;'><h2>Achievement not found</h2><a href='achievements.php'>Return to Achievements</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Set dynamic SEO meta tags
$page_title = htmlspecialchars($item['title']) . " | " . ($data['seo']['title'] ?? 'Portfolio');
$meta_description = htmlspecialchars($item['short_description']);

$og_image = !empty($item['certificate_image']) ? $item['certificate_image'] : null;

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container" style="padding: 100px 0 50px;">
    <a href="achievements.php" style="color: var(--accent-color); text-decoration: none; margin-bottom: 20px; display: inline-block;">
        <i class="fas fa-arrow-left"></i> Back to Achievements
    </a>
    
    <div style="background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden;">
        <?php if(!empty($item['certificate_image'])): ?>
            <div style="width: 100%; max-height: 400px; overflow: hidden; background: #111;">
                <img src="<?php echo htmlspecialchars($item['certificate_image']); ?>" alt="Certificate" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
        <?php endif; ?>
        
        <div style="padding: 40px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 20px; flex-wrap:wrap; gap:20px;">
                <div>
                    <h1 style="font-size: 2.2em; margin-bottom: 10px;"><?php echo htmlspecialchars($item['title']); ?></h1>
                    <div style="color: #aaa; font-size: 1.1em;">
                        <strong><i class="fas fa-building"></i> <?php echo htmlspecialchars($item['organization']); ?></strong>
                    </div>
                </div>
                
                <div style="text-align:right;">
                    <span style="display:inline-block; background:var(--accent-color); color:#fff; padding:6px 15px; border-radius:20px; font-weight:bold; margin-bottom:10px;">
                        <?php echo htmlspecialchars($item['category']); ?>
                    </span>
                    <div style="color: #888;">
                        <i class="fas fa-calendar-check"></i> Completed: <?php echo htmlspecialchars(date('F d, Y', strtotime($item['completion_date']))); ?>
                    </div>
                </div>
            </div>
            
            <hr style="border-color: #333; margin: 30px 0;">
            
            <div style="line-height: 1.8; font-size: 1.1em; color: #ccc;">
                <!-- Full Description rendered as HTML (Rich Text) -->
                <?php echo $item['long_description']; ?>
            </div>
            
            <?php if(!empty($item['database_subject'])): ?>
            <div style="margin-top: 40px; padding: 20px; background: rgba(97, 175, 239, 0.1); border-left: 4px solid #61afef; border-radius: 4px;">
                <h4 style="color: #61afef; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Related Subject</h4>
                <p style="margin:0;"><?php echo htmlspecialchars($item['database_subject']); ?></p>
            </div>
            <?php endif; ?>

            <?php if(!empty($item['verification_link'])): ?>
            <div style="margin-top: 40px;">
                <a href="<?php echo htmlspecialchars($item['verification_link']); ?>" target="_blank" class="btn" style="background: var(--accent-color); color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display:inline-flex; align-items:center; gap:10px;">
                    <i class="fas fa-external-link-alt"></i> Verify Credential
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
