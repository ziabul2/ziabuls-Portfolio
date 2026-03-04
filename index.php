<?php
// Load portfolio data
require_once __DIR__ . '/helpers/data_loader.php';
require_once __DIR__ . '/helpers/AnalyticsManager.php';

$data = loadPortfolioData();

// Track visit
$analytics = new AnalyticsManager();
$analytics->trackVisit();
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/includes/head.php'; ?>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<?php
require_once __DIR__ . '/helpers/SectionRenderer.php';
$renderer = new SectionRenderer($data);
$renderer->renderAll();
?>

<?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- JS -->
    <script src="js/script.js"></script>
</body>
</html>
