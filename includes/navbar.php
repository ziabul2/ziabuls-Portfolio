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
            <a href="index.php" class="logo">
                <i class="fas fa-terminal"></i> <?php echo $data['hero']['name']; ?>
            </a>
            <?php 
            $current_page = basename($_SERVER['PHP_SELF']);
            $is_home = ($current_page == 'index.php' || $current_page == '');
            $prefix = $is_home ? '' : 'index.php';
            ?>
            <div class="nav-menu">
                <a href="<?php echo $prefix; ?>#home" class="nav-link"><span>#</span>home</a>
                <a href="<?php echo $prefix; ?>#projects" class="nav-link"><span>#</span>projects</a>
                <a href="<?php echo $prefix; ?>#skills" class="nav-link"><span>#</span>skills</a>
                <a href="<?php echo $prefix; ?>#about-me" class="nav-link"><span>#</span>about-me</a>
                <a href="blog.php" class="nav-link <?php echo ($current_page == 'blog.php' || $current_page == 'post.php') ? 'active' : ''; ?>"><span>#</span>blog</a>
                <a href="<?php echo $prefix; ?>#contacts" class="nav-link"><span>#</span>contacts</a>
            </div>
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>
