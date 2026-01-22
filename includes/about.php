    <!-- About Me Section -->
    <section id="about-me" class="about">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span><?php echo $data['about_section']['title']; ?></h2>
                <div class="section-line"></div>
            </div>
            
            <div class="about-content">
                <div class="about-text">
                    <p><?php echo $data['about_section']['intro']; ?></p>
                    <?php foreach ($data['about_section']['paragraphs'] as $paragraph): ?>
                    <p><?php echo $paragraph; ?></p>
                    <?php endforeach; ?>
                    <br>
                    <a href="<?php echo $data['about_section']['button_link']; ?>" class="btn"><?php echo $data['about_section']['button_text']; ?></a>
                </div>
                <div class="about-img">
                   <img src="<?php echo $data['about_section']['image']; ?>" alt="Profile" style="border-bottom:none; opacity:0.8;">
                </div>
            </div>
        </div>
    </section>
