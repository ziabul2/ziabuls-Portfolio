<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../helpers/AnalyticsManager.php';

$analytics = new AnalyticsManager();
$stats = $analytics->getStats();

// Prepare Daily Activity Chart Data
$last7Days = [];
for($i = 6; $i >= 0; $i--) {
    $last7Days[] = date('Y-m-d', strtotime("-$i days"));
}

$chartLabels = [];
$chartData = [];
foreach($last7Days as $day) {
    $chartLabels[] = date('M d', strtotime($day));
    $chartData[] = $stats['daily_stats'][$day] ?? 0;
}

// CEO Insights
$topPage = !empty($stats['pages']) ? array_key_first($stats['pages']) : 'N/A';
$topBrowser = !empty($stats['browsers']) ? array_search(max($stats['browsers']), $stats['browsers']) : 'N/A';
$topDevice = !empty($stats['devices']) ? array_search(max($stats['devices']), $stats['devices']) : 'N/A';
$todayCount = $stats['daily_stats'][date('Y-m-d')] ?? 0;

?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="section-header" style="margin-bottom: 30px;">
    <h1><i class="fas fa-chart-pie"></i> CEO Analytics Dashboard</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<!-- TOP INSIGHT CARDS -->
<div class="dashboard-grid">
    <div class="stat-card" style="border-top: 4px solid #c778dd;">
        <div class="stat-info">
            <p style="color: #888; font-size: 0.8rem; text-transform: uppercase;">Total Traffic</p>
            <h3><?php echo number_format($stats['total_visits']); ?></h3>
            <small style="color: #98c379;"><i class="fas fa-users"></i> <?php echo number_format($stats['unique_visitors']); ?> Unique</small>
        </div>
    </div>
    <div class="stat-card" style="border-top: 4px solid #61afef;">
        <div class="stat-info">
            <p style="color: #888; font-size: 0.8rem; text-transform: uppercase;">Most Popular Page</p>
            <h3 style="font-size: 1.2rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($topPage); ?></h3>
            <small style="color: #61afef;"><i class="fas fa-star"></i> Top Performance</small>
        </div>
    </div>
    <div class="stat-card" style="border-top: 4px solid #e5c07b;">
        <div class="stat-info">
            <p style="color: #888; font-size: 0.8rem; text-transform: uppercase;">Top Device / OS</p>
            <h3><?php echo htmlspecialchars($topDevice); ?></h3>
            <small style="color: #e5c07b;"><i class="fas fa-mobile-alt"></i> Main Platform</small>
        </div>
    </div>
    <div class="stat-card" style="border-top: 4px solid #98c379;">
        <div class="stat-info">
            <p style="color: #888; font-size: 0.8rem; text-transform: uppercase;">Today's Growth</p>
            <h3>+<?php echo number_format($todayCount); ?></h3>
            <small style="color: #98c379;"><i class="fas fa-chart-line"></i> New Visits</small>
        </div>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: 2fr 1fr; margin-top: 30px;">
    
    <!-- MAIN TRAFFIC CHART -->
    <div class="editor-card">
        <h3><i class="fas fa-chart-line"></i> Traffic Overview (Last 7 Days)</h3>
        <div style="margin-top: 20px; height: 300px;">
            <canvas id="trafficChart"></canvas>
        </div>
    </div>

    <!-- DEVICE DISTRIBUTION -->
    <div class="editor-card">
        <h3><i class="fas fa-desktop"></i> Device Reach</h3>
        <div style="margin-top: 20px; height: 250px; display: flex; justify-content: center;">
            <canvas id="deviceChart"></canvas>
        </div>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; margin-top: 30px;">
    
    <!-- TOP PAGES TABLE -->
    <div class="editor-card">
        <h3><i class="fas fa-file-alt"></i> Page Performance</h3>
        <div style="overflow-x: auto; margin-top: 20px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 1px solid #444; text-align: left;">
                        <th style="padding: 10px;">Page Path</th>
                        <th style="padding: 10px; text-align: right;">Views</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $pageCount = 0;
                    foreach($stats['pages'] as $path => $count): 
                        if ($pageCount >= 8) break;
                        $pageCount++;
                    ?>
                    <tr style="border-bottom: 1px solid #222;">
                        <td style="padding: 10px; color: #ccc;"><?php echo htmlspecialchars($path); ?></td>
                        <td style="padding: 10px; text-align: right; color: var(--accent-color); font-weight: bold;"><?php echo number_format($count); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- BROWSER & REFERRER -->
    <div class="editor-card">
        <h3><i class="fas fa-globe"></i> Browser Popularity</h3>
        <div style="margin-top: 20px; height: 200px; display: flex; justify-content: center; margin-bottom: 30px;">
            <canvas id="browserChart"></canvas>
        </div>
        
        <h3 style="border-top: 1px solid #333; padding-top: 20px;"><i class="fas fa-link"></i> Top Traffic Sources</h3>
        <ul style="list-style: none; padding: 0; margin-top: 15px;">
            <?php 
            $refCount = 0;
            foreach($stats['referrers'] as $host => $count): 
                if ($refCount >= 5) break; $refCount++;
            ?>
                <li style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #222; font-size: 0.9rem;">
                    <span style="color: #aaa;"><?php echo htmlspecialchars($host); ?></span>
                    <span style="font-weight: bold; color: #61afef;"><?php echo $count; ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- GOOGLE ANALYTICS INTEGRATION -->
    <div class="editor-card">
        <h3 style="color: #4285F4;"><i class="fab fa-google"></i> Google Tag Integration</h3>
        <p style="font-size: 0.85rem; color: #888; margin: 15px 0;">Your site is connected to Google Analytics via the following tags:</p>
        
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
            <?php 
            require_once __DIR__ . '/../helpers/data_loader.php';
            $portData = loadPortfolioData();
            $tags = $portData['seo']['google_tags'] ?? [$portData['seo']['google_analytics'] ?? 'N/A'];
            foreach ($tags as $tag): 
            ?>
                <div style="background: rgba(66, 133, 244, 0.1); border: 1px solid rgba(66, 133, 244, 0.3); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; color: #4285F4; font-family: monospace;">
                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($tag); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 8px; border: 1px dashed #444; text-align: center;">
            <p style="font-size: 0.85rem; color: #ccc; margin-bottom: 15px;">To see real-time visitors, bounce rate, and world-wide demographics, visit your primary dashboard:</p>
            <a href="https://analytics.google.com/" target="_blank" class="btn-login" style="display: inline-block; background: #4285F4; border: none; padding: 10px 25px;">
                <i class="fas fa-external-link-alt"></i> View Live Reports
            </a>
        </div>
        
        <div style="margin-top: 20px; font-size: 0.75rem; color: #666;">
            <i class="fas fa-info-circle"></i> Google Analytics data may take 24-48 hours to appear in full reports after installation.
        </div>
    </div>
</div>

<script>
    // Theme colors
    const accentColor = '#61afef'; // Blue
    const secondaryColor = '#c778dd'; // Purple
    const successColor = '#98c379'; // Green
    const warningColor = '#e5c07b'; // Yellow
    const textColor = '#abb2bf';
    const gridColor = 'rgba(255, 255, 255, 0.05)';

    // 1. Traffic Line Chart
    new Chart(document.getElementById('trafficChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Visits',
                data: <?php echo json_encode($chartData); ?>,
                borderColor: accentColor,
                backgroundColor: 'rgba(97, 175, 239, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: accentColor
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: gridColor }, ticks: { color: textColor } },
                x: { grid: { display: false }, ticks: { color: textColor } }
            }
        }
    });

    // 2. Device Pie Chart
    <?php
    $deviceLabels = array_keys($stats['devices']);
    $deviceData = array_values($stats['devices']);
    ?>
    new Chart(document.getElementById('deviceChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($deviceLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($deviceData); ?>,
                backgroundColor: [accentColor, secondaryColor, successColor, warningColor, '#e06c75', '#d19a66'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: textColor, padding: 20 } }
            },
            cutout: '70%'
        }
    });

    // 3. Browser Chart (Horizontal Bar or Semi-pie)
    <?php
    $browserLabels = array_keys($stats['browsers']);
    $browserData = array_values($stats['browsers']);
    ?>
    new Chart(document.getElementById('browserChart'), {
        type: 'polarArea',
        data: {
            labels: <?php echo json_encode($browserLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($browserData); ?>,
                backgroundColor: ['rgba(97, 175, 239, 0.5)', 'rgba(199, 120, 221, 0.5)', 'rgba(152, 195, 121, 0.5)', 'rgba(229, 192, 123, 0.5)'],
                borderColor: 'rgba(255,255,255,0.1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { color: textColor } }
            },
            scales: {
                r: { grid: { color: gridColor }, ticks: { display: false } }
            }
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
