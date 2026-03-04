<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../config/security.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die('Invalid CSRF token.');
    }

    $title = sanitizeInput($_POST['title']);
    $icon = sanitizeInput($_POST['icon'] ?? 'fas fa-list');
    $id = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $title));
    
    $sectionsFile = __DIR__ . '/../../data/home_sections.json';
    $sections = json_decode(file_get_contents($sectionsFile), true);

    // Check if ID exists
    foreach ($sections as $s) {
        if ($s['id'] === $id) {
            $id .= '_' . time();
            break;
        }
    }

    $dataFile = 'data/' . $id . '.json';
    $absoluteDataFile = __DIR__ . '/../../' . $dataFile;

    // Create unique data file
    if (!file_exists($absoluteDataFile)) {
        file_put_contents($absoluteDataFile, json_encode([], JSON_PRETTY_PRINT));
    }

    $newSection = [
        "id" => $id,
        "title" => $title,
        "type" => "dynamic_list",
        "visible" => true,
        "order" => count($sections) + 1,
        "data_file" => $dataFile,
        "icon" => $icon
    ];

    $sections[] = $newSection;
    file_put_contents($sectionsFile, json_encode($sections, JSON_PRETTY_PRINT));

    setFlashMessage("New section '$title' created successfully!");
    header('Location: ../manage-sections.php');
    exit;
}
