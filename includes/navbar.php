    <!-- Social Sidebar (Left Fixed) -->
    <div class="social-sidebar">
        <div class="social-line"></div>
        <?php foreach ($data['social_links'] as $link): ?>
            <a href="<?php echo $link['url']; ?>" target="_blank" class="social-icon">
                <i class="<?php echo $link['icon']; ?>"></i>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="#" class="logo">
                <i class="fas fa-terminal"></i> <?php echo $data['hero']['name']; ?>
            </a>
            <div class="nav-menu">
                <a href="#home" class="nav-link"><span>#</span>home</a>
                <a href="#projects" class="nav-link"><span>#</span>projects</a>
                <a href="#skills" class="nav-link"><span>#</span>skills</a>
                <a href="#about-me" class="nav-link"><span>#</span>about-me</a>
                <a href="#contacts" class="nav-link"><span>#</span>contacts</a>
            </div>
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>
