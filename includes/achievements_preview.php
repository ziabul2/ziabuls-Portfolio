<?php
require_once __DIR__ . '/../helpers/AchievementManager.php';
require_once __DIR__ . '/../admin/includes/functions.php';

$achievementManager = new AchievementManager();
$recentAchievements = array_slice($achievementManager->getAchievements(), 0, 3);
$portfolioData = getPortfolioData();
$sect = $portfolioData['achievements_section'] ?? ['title' => 'latest-achievements', 'view_all_text' => 'View all ~~>', 'view_all_link' => 'achievements.php'];
?>
<section id="achievements" class="projects" style="padding: 60px 0; background-color: var(--bg-color);">
    <div class="container">
        <div class="section-header">
            <h2><span>#</span><?php echo htmlspecialchars($sect['title']); ?></h2>
            <div class="section-line"></div>
            <a href="<?php echo htmlspecialchars($sect['view_all_link']); ?>" class="btn btn-sm"><?php echo htmlspecialchars($sect['view_all_text']); ?></a>
        </div>
        
        <?php if (empty($recentAchievements)): ?>
            <p style="text-align: center; color: var(--text-color);">No achievements published yet.</p>
        <?php else: ?>
            <div class="project-grid">
                <?php foreach ($recentAchievements as $item): ?>
                    <div class="project-card">
                        <?php if(!empty($item['certificate_image'])): ?>
                            <div class="project-img">
                                <img src="<?php echo htmlspecialchars($item['certificate_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="project-tech"><?php echo htmlspecialchars($item['category']); ?> | <?php echo htmlspecialchars(date('M Y', strtotime($item['completion_date']))); ?></div>
                        
                        <div class="project-content">
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p><?php echo htmlspecialchars($item['short_description']); ?></p>
                            <a href="achievement_details.php?id=<?php echo $item['id']; ?>" class="btn btn-sm">View Details &lt;~&gt;</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
