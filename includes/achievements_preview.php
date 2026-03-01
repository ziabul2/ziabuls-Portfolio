<?php
require_once __DIR__ . '/../helpers/AchievementManager.php';
$achievementManager = new AchievementManager();
$recentAchievements = array_slice($achievementManager->getAchievements(), 0, 3);
?>
<section id="achievements" class="blog-section" style="padding: 60px 0; background-color: var(--bg-color);">
    <div class="container">
        <h2 class="section-title">Latest <span style="color:var(--accent-color)">Achievements</span></h2>
        
        <?php if (empty($recentAchievements)): ?>
            <p style="text-align: center; color: var(--text-color);">No achievements published yet.</p>
        <?php else: ?>
            <div class="blog-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                <?php foreach ($recentAchievements as $item): ?>
                    <article class="blog-card" style="background: rgba(255,255,255,0.02); border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); transition: transform 0.3s; height:100%; display:flex; flex-direction:column;">
                        <?php if(!empty($item['certificate_image'])): ?>
                            <div class="blog-image" style="height: 200px; overflow: hidden;">
                                <img src="<?php echo htmlspecialchars($item['certificate_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;">
                            </div>
                        <?php endif; ?>
                        
                        <div class="blog-content" style="padding: 20px; flex:1; display:flex; flex-direction:column;">
                            <div class="blog-meta" style="margin-bottom: 10px; font-size: 0.9em; color: var(--accent-color);">
                                <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars(date('M d, Y', strtotime($item['completion_date']))); ?></span>
                                <span style="margin-left:15px;"><i class="fas fa-folder"></i> <?php echo htmlspecialchars($item['category']); ?></span>
                            </div>
                            <h3 class="blog-title" style="margin-bottom: 15px; font-size: 1.3em;">
                                <a href="achievement_details.php?id=<?php echo $item['id']; ?>" style="color: var(--text-color); text-decoration: none;">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </h3>
                            <p class="blog-excerpt" style="color: #aaa; margin-bottom: 20px; line-height: 1.6; flex:1;">
                                <?php echo htmlspecialchars($item['short_description']); ?>
                            </p>
                            <a href="achievement_details.php?id=<?php echo $item['id']; ?>" class="read-more" style="color: var(--accent-color); text-decoration: none; font-weight: bold; align-self:flex-start;">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <div class="view-all-container" style="text-align: center; margin-top: 40px;">
                <a href="achievements.php" class="btn" style="padding: 12px 30px; background: var(--accent-color); color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; transition: opacity 0.3s;">All Achievements</a>
            </div>
        <?php endif; ?>
    </div>
</section>
