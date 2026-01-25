<?php
/**
 * Unified API Endpoint: Save Portfolio Updates
 * 
 * Handles all portfolio data updates with atomic operations
 * 
 * Expected POST parameters:
 *   - section: string (hero, skills, projects, etc.)
 *   - data: JSON string or form fields
 *   
 * Returns JSON response with success, message, backup info
 */

require_once __DIR__ . '/../helpers/AtomicUpdateController.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get section name
$section = $_POST['section'] ?? null;
if (!$section) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing section parameter']);
    exit;
}

// Extract data - handle both JSON and form data
$data = [];
$raw_data = $_POST['data'] ?? null;

if ($raw_data && is_string($raw_data)) {
    // If data is sent as JSON string
    $data = json_decode($raw_data, true);
} else {
    // Otherwise, assume all POST fields except 'section' are data
    $data = $_POST;
    unset($data['section']);
}

// Sanitize and prepare data
$data = sanitizeUpdate($data);

// Perform atomic update
$controller = new AtomicUpdateController();
$result = $controller->update($section, $data);

// Return response
http_response_code($result['success'] ? 200 : 400);
echo json_encode($result);
exit;

/**
 * Sanitize user input while preserving data structure
 */
function sanitizeUpdate($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = sanitizeUpdate($value);
            } elseif (is_string($value)) {
                // Remove null bytes, excessive whitespace
                $value = preg_replace('/\x00/', '', $value);
                $value = preg_replace('/\s+/', ' ', $value);
                // Don't HTML-encode yet - keep as-is for data
                $data[$key] = trim($value);
            }
        }
    } elseif (is_string($data)) {
        $data = preg_replace('/\x00/', '', $data);
        $data = trim($data);
    }
    
    return $data;
}
?>
