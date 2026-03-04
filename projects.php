<?php 
require_once __DIR__ . '/helpers/data_loader.php';
$data = loadPortfolioData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects | ZIMBABU</title>
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/ziabul islam - non bg.png">
</head>
<body>

    <!-- Social Sidebar -->
    <div class="social-sidebar">
        <div class="social-line"></div>
        <a href="https://github.com/ziabul2" target="_blank" class="social-icon"><i class="fab fa-github"></i></a>
        <a href="https://www.linkedin.com/in/md-ziabul-islam-zimbabu-14b5b21a3/" target="_blank" class="social-icon"><i class="fab fa-linkedin"></i></a>
        <a href="https://www.facebook.com/ziabul123" target="_blank" class="social-icon"><i class="fab fa-facebook"></i></a>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-terminal"></i> ZIMBABU
            </a>
            <div class="nav-menu">
                <a href="index.php" class="nav-link"><span>#</span>home</a>
                <a href="projects.php" class="nav-link active"><span>#</span>projects</a>
                <a href="index.php#skills" class="nav-link"><span>#</span>skills</a>
                <a href="index.php#about-me" class="nav-link"><span>#</span>about-me</a>
                <a href="index.php#contacts" class="nav-link"><span>#</span>contacts</a>
            </div>
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Projects Header -->
    <section class="hero" style="padding-top: 40px; margin-bottom: 40px;">
        <div class="container">
            <div class="hero-text">
                <h1><span>/</span>projects</h1>
                <p>List of my projects</p>
            </div>
        </div>
    </section>

    <!-- Complete Project List -->
    <section class="projects">
        <div class="container">
            <div class="section-header">
                <h2><span>#</span>complete-apps</h2>
                <div class="section-line"></div>
            </div>
            
            <div class="project-grid">
                <?php foreach ($data['projects_section']['items'] as $project): ?>
                <div class="project-card">
                    <div class="project-img">
                        <img src="<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>">
                    </div>
                    <div class="project-tags"><?php echo htmlspecialchars($project['technologies']); ?></div>
                    <div class="project-info">
                        <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                        <p><?php echo htmlspecialchars($project['description']); ?></p>
                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <a href="project_details.php?id=<?php echo urlencode($project['title']); ?>" class="btn btn-sm">Details -></a>
                            <?php if(!empty($project['link_url']) && $project['link_url'] !== '#'): ?>
                                <a href="<?php echo htmlspecialchars($project['link_url']); ?>" target="_blank" class="btn btn-sm"><?php echo htmlspecialchars($project['link_text']); ?> <~></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <div class="logo">
                         <i class="fas fa-terminal"></i> ZIMBABU
                    </div>
                    <p>ziabulislam2222@gmail.com</p>
                    <p>Computer Engineer</p>
                </div>
                <div class="footer-right">
                    <h3>Media</h3>
                    <div class="footer-socials">
                        <a href="https://github.com/ziabul2" target="_blank"><i class="fab fa-github"></i></a>
                        <a href="https://www.facebook.com/ziabul123" target="_blank"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.linkedin.com/in/md-ziabul-islam-zimbabu-14b5b21a3/" target="_blank"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div style="text-align:center; margin-top:30px; font-size:0.9rem; color:#666;">
                &copy; Copyright <?php echo date("Y"); ?>. Made by Ziabul Islam.
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" title="Go to top" style="display: none; position: fixed; bottom: 30px; right: 30px; z-index: 10000; border: none; outline: none; background-color: var(--primary-color); color: white; cursor: pointer; padding: 15px; border-radius: 50%; font-size: 18px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3); opacity: 1; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-arrow-up"></i></button>

    <!-- JS -->
    <script src="js/script.js"></script>
</body>
</html>
