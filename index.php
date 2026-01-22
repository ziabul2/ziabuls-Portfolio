<?php
// Load portfolio data
require_once __DIR__ . '/helpers/data_loader.php';
$data = loadPortfolioData();
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/includes/head.php'; ?>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<?php include __DIR__ . '/includes/hero.php'; ?>

<?php include __DIR__ . '/includes/projects.php'; ?>

<?php include __DIR__ . '/includes/skills.php'; ?>

<?php include __DIR__ . '/includes/about.php'; ?>
<?php include __DIR__ . '/includes/blog_preview.php'; ?>
<?php include __DIR__ . '/includes/contact.php'; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- JS -->
    <script src="js/script.js"></script>
</body>
</html>
