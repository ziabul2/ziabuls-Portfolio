<?php
header('Content-Type: application/xml; charset=utf-8');

// Load portfolio data via helper
require_once __DIR__ . '/helpers/data_loader.php';
$data = loadPortfolioData();

// Auto-detect base URL for zero-config portability
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base_url = rtrim($protocol . $domainName . $scriptDir, '/');

// Current date for lastmod
$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// 1. Static/Main Pages
$static_pages = [
    '' => '1.0',
    'projects.php' => '0.8',
    'achievements.php' => '0.7',
    'resume.php' => '0.7'
];

foreach ($static_pages as $page => $priority) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . $base_url . '/' . $page . '</loc>' . PHP_EOL;
    echo '    <lastmod>' . $today . '</lastmod>' . PHP_EOL;
    echo '    <changefreq>weekly</changefreq>' . PHP_EOL;
    echo '    <priority>' . $priority . '</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}

// 2. Dynamic Projects
if (isset($data['projects_section']['items'])) {
    foreach ($data['projects_section']['items'] as $project) {
        if (!empty($project['title'])) {
            // Slugify title for better SEO (assuming project_details.php handles it or use index)
            $id = urlencode($project['title']);
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . $base_url . '/project_details.php?id=' . $id . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . $today . '</lastmod>' . PHP_EOL;
            echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
            echo '    <priority>0.6</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }
    }
}

// 3. Dynamic Blog/News Posts
if (isset($data['blog_posts'])) {
    foreach ($data['blog_posts'] as $post) {
        if (($post['status'] ?? '') === 'published') {
            $date = !empty($post['date']) ? date('Y-m-d', strtotime($post['date'])) : $today;
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . $base_url . '/blog.php?id=' . ($post['id'] ?? '') . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . $date . '</lastmod>' . PHP_EOL;
            echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
            echo '    <priority>0.6</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }
    }
}

echo '</urlset>';
?>
