    <!-- Achievements Section -->
    <section id="achievements" class="projects">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span>Achievements</h2>
                <div class="section-line"></div>
                <a href="achievements.php" class="btn btn-sm">View All =></a>
            </div>
            
            <div class="project-grid">
                <?php 
                try {
                    require_once __DIR__ . '/../helpers/AchievementManager.php';
                    $achievementManager = AchievementManager::getInstance();
                    $all_achievements = $achievementManager->getAchievements();
                    
                    // Slice first 3
                    $latest_achievements = array_slice($all_achievements, 0, 3);
                } catch (Exception $e) {
                    error_log('Error fetching achievements for homepage: ' . $e->getMessage());
                    $latest_achievements = [];
                }
                
                if (empty($latest_achievements)): ?>
                    <p style="color: #666; font-style: italic;">No achievements added yet.</p>
                <?php else: ?>
                    <?php foreach ($latest_achievements as $achievement): ?>
                    <!-- Achievement Card -->
                    <div class="project-card">
                        <?php if(!empty($achievement['certificate_image'])): ?>
                        <div class="project-img">
                            <img src="<?php echo htmlspecialchars($achievement['certificate_image']); ?>" alt="<?php echo htmlspecialchars($achievement['title']); ?>">
                        </div>
                        <?php endif; ?>
                        <div class="project-tech">
                            <?php 
                            $dateStr = $achievement['completion_date'] ?? $achievement['created_at'] ?? 'now';
                            echo date('M d, Y', strtotime($dateStr)); 
                            ?>
                            <?php if(!empty($achievement['organization'])): ?>
                                • <?php echo htmlspecialchars($achievement['organization']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="project-content">
                            <h3><?php echo htmlspecialchars($achievement['title']); ?></h3>
                            <p><?php echo htmlspecialchars($achievement['short_description'] ?? ''); ?></p>
                            <a href="achievement_details.php?id=<?php echo urlencode($achievement['id']); ?>" class="btn btn-sm">View Details</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
