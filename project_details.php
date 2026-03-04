<?php
require_once __DIR__ . '/helpers/data_loader.php';
$data = loadPortfolioData();

$projectId = $_GET['id'] ?? '';
$project = null;

foreach ($data['projects_section']['items'] as $item) {
    if ($item['title'] === $projectId || basename($item['image']) === $projectId) { // Simple matching
        $project = $item;
        break;
    }
}

// Fallback search by title if URL encoded
if (!$project) {
    foreach ($data['projects_section']['items'] as $item) {
        if (urlencode($item['title']) === $projectId) {
            $project = $item;
            break;
        }
    }
}

if (!$project) {
    header('Location: projects.php');
    exit;
}

// SEO variables
$page_title = $project['title'] . " | Project Detail";
$og_title = $project['title'];
$og_description = $project['description'];
$og_image = $project['image'];

include __DIR__ . '/includes/head.php';
?>

<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container" style="margin-top: 120px; margin-bottom: 80px; max-width: 900px;">
        <div class="project-detail-header" style="margin-bottom: 40px;">
            <a href="projects.php" style="color: var(--accent-color); text-decoration: none; display: inline-block; margin-bottom: 20px;">
                <i class="fas fa-arrow-left"></i> Back to Projects
            </a>
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?php echo htmlspecialchars($project['title']); ?></h1>
            <div style="color: var(--accent-color); font-family: 'Fira Code', monospace; margin-bottom: 20px;">
                <i class="fas fa-code"></i> <?php echo htmlspecialchars($project['technologies']); ?>
            </div>
        </div>

        <?php if(!empty($project['image'])): ?>
        <div class="project-main-image" style="margin-bottom: 40px; border-radius: 8px; overflow: hidden; border: 1px solid #333;">
            <img src="<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" style="width: 100%; height: auto; display: block;">
        </div>
        <?php endif; ?>

        <div class="project-content" style="color: #ccc; line-height: 1.8; font-size: 1.1rem;">
            <div style="background: rgba(255,255,255,0.05); padding: 25px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid var(--accent-color);">
                <p><?php echo htmlspecialchars($project['description']); ?></p>
            </div>

            <div class="case-study-content">
                <?php 
                if (!empty($project['long_description'])) {
                    echo $project['long_description']; 
                } else {
                    echo '<p style="color: #666; font-style: italic;">No detailed case study available for this project yet.</p>';
                }
                ?>
            </div>
        </div>

        <div style="margin-top: 50px; display: flex; gap: 20px;">
            <?php if(!empty($project['link_url']) && $project['link_url'] !== '#'): ?>
                <a href="<?php echo htmlspecialchars($project['link_url']); ?>" target="_blank" class="btn">
                    <i class="fas fa-external-link-alt"></i> <?php echo htmlspecialchars($project['link_text']); ?>
                </a>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="js/script.js"></script>
</body>
</html>
