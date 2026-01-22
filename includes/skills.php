    <!-- Skills Section -->
    <section id="skills" class="skills">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span><?php echo $data['skills_section']['title']; ?></h2>
                <div class="section-line"></div>
            </div>
            
            <div class="skills-content">
                <div class="skills-decor">
                   <!-- Decorative shapes -->
                   <div style="margin:20px; color:var(--text-color);">
                       <i class="fas fa-shapes fa-2x"></i>
                       <br><br>
                       <i class="fas fa-draw-polygon fa-2x"></i>
                   </div>
                </div>
                
                <div class="skills-boxes">
                    <?php foreach ($data['skills_section']['categories'] as $category): ?>
                    <div class="skill-box">
                        <div class="skill-title"><?php echo $category['name']; ?></div>
                        <div class="skill-list">
                            <?php echo implode('<br>', $category['items']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
