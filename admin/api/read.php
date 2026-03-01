<?php
/**
 * API Endpoint: Read Portfolio Section
 * 
 * Returns the current data for a specific section
 * 
 * Usage:
 *   GET /admin/api/read.php?section=hero
 *   
 * Returns:
 *   {
 *     "success": true,
 *     "section": "hero",
 *     "data": {...},
 *     "timestamp": 1234567890,
 *     "version": "1.0"
 *   }
 */

header('Content-Type: application/json');

// Only accept GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$section = $_GET['section'] ?? null;

if (!$section) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing section parameter']);
    exit;
}

// Load portfolio data
$portfolio_path = __DIR__ . '/../../data/portfolio.json';

if (!file_exists($portfolio_path)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Portfolio data not found']);
    exit;
}

$json = file_get_contents($portfolio_path);
$data = json_decode($json, true);

if (!is_array($data) || !isset($data[$section])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => "Section '{$section}' not found"]);
    exit;
}

// Return the section data
echo json_encode([
    'success' => true,
    'section' => $section,
    'data' => $data[$section],
    'timestamp' => time(),
    'version' => '1.0'
]);
exit;
?>
