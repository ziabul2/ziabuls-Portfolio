<?php
require_once __DIR__ . '/helpers/data_loader.php';
$data = loadPortfolioData();

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/helpers/AchievementManager.php';

$achievementManager = new AchievementManager();
$category = $_GET['category'] ?? null;
$totalAchievements = $achievementManager->getAchievements($category);
?>

<div class="page-header" style="padding: 100px 0 50px; text-align: center; background: rgba(0,0,0,0.2);">
    <div class="container">
        <h1 style="font-size: 2.5em; margin-bottom: 15px;">My <span style="color:var(--accent-color)">Achievements</span></h1>
        <p style="color: #aaa; max-width: 600px; margin: 0 auto;">A showcase of my career milestones, certificates, and coding accomplishments.</p>
    </div>
</div>

<div class="container" style="padding: 50px 0;">
    <!-- Filter Links -->
    <div style="text-align: center; margin-bottom: 40px;">
        <a href="achievements.php" class="btn <?php echo !$category ? 'btn-active' : ''; ?>" style="margin: 0 5px 10px; padding: 8px 20px; text-decoration:none; <?php echo !$category ? 'background:var(--accent-color); color:#fff; border-radius:20px;' : 'color:#888; border:1px solid #444; border-radius:20px;'; ?>">All</a>
        
        <?php 
        $categories = ['Academic', 'Programming', 'Competition', 'Other']; 
        foreach ($categories as $cat): 
        ?>
            <a href="achievements.php?category=<?php echo urlencode($cat); ?>" class="btn" style="margin: 0 5px 10px; padding: 8px 20px; text-decoration:none; <?php echo $category === $cat ? 'background:var(--accent-color); color:#fff; border-radius:20px;' : 'color:#888; border:1px solid #444; border-radius:20px;'; ?>">
                <?php echo htmlspecialchars($cat); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($totalAchievements)): ?>
        <div style="text-align: center; padding: 50px; background: rgba(255,255,255,0.02); border-radius: 8px;">
            <i class="fas fa-award" style="font-size: 48px; color: #444; margin-bottom: 20px;"></i>
            <h3 style="color: #888;">No achievements found in this category.</h3>
        </div>
    <?php else: ?>
        <div class="blog-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
            <?php foreach ($totalAchievements as $item): ?>
                <article class="blog-card" style="background: rgba(255,255,255,0.02); border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; transition: transform 0.3s;">
                    <?php if(!empty($item['certificate_image'])): ?>
                        <div class="blog-image" style="height: 200px; overflow: hidden; position:relative;">
                            <img src="<?php echo htmlspecialchars($item['certificate_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position:absolute; top:10px; right:10px; background:var(--accent-color); color:#fff; padding:4px 10px; border-radius:4px; font-size:0.8em; font-weight:bold;">
                                <?php echo htmlspecialchars($item['category']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="blog-content" style="padding: 25px; flex:1; display:flex; flex-direction:column;">
                        <h3 class="blog-title" style="margin-bottom: 10px; font-size: 1.4em;">
                            <a href="achievement_details.php?id=<?php echo $item['id']; ?>" style="color: var(--text-color); text-decoration: none;">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                        </h3>
                        
                        <div style="margin-bottom: 15px; color:#888; font-size:0.9em;">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($item['organization']); ?> • 
                            <i class="fas fa-calendar"></i> <?php echo htmlspecialchars(date('M Y', strtotime($item['completion_date']))); ?>
                        </div>

                        <p class="blog-excerpt" style="color: #aaa; margin-bottom: 20px; line-height: 1.6; flex:1;">
                            <?php echo htmlspecialchars($item['short_description']); ?>
                        </p>
                        
                        <a href="achievement_details.php?id=<?php echo $item['id']; ?>" class="read-more" style="color: var(--accent-color); text-decoration: none; font-weight: bold; align-self:flex-start;">Review Achievement <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
