    <!-- Projects Section -->
    <section id="projects" class="projects">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span><?php echo $data['projects_section']['title']; ?></h2>
                <div class="section-line"></div>
                <a href="<?php echo $data['projects_section']['view_all_link']; ?>" class="btn btn-sm"><?php echo $data['projects_section']['view_all_text']; ?></a>
            </div>
            
            <div class="project-grid">
                <?php foreach ($data['projects_section']['items'] as $project): ?>
                <!-- Project -->
                <div class="project-card">
                    <div class="project-img">
                        <img src="<?php echo $project['image']; ?>" alt="<?php echo $project['title']; ?>">
                    </div>
                    <div class="project-tech"><?php echo $project['technologies']; ?></div>
                    <div class="project-content">
                        <h3><?php echo $project['title']; ?></h3>
                        <p><?php echo $project['description']; ?></p>
                        <div style="display:flex; gap:10px;">
                            <a href="project_details.php?id=<?php echo urlencode($project['title']); ?>" class="btn btn-sm">Case Study -></a>
                            <?php if(!empty($project['link_url']) && $project['link_url'] !== '#'): ?>
                                <a href="<?php echo $project['link_url']; ?>" target="_blank" class="btn btn-sm"><?php echo $project['link_text']; ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
