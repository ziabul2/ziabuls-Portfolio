<?php
require_once __DIR__ . '/helpers/data_loader.php';
require_once __DIR__ . '/helpers/LeadManager.php';
require_once __DIR__ . '/admin/includes/functions.php'; // For sanitization

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple Anti-Spam (Honeypot)
    if (!empty($_POST['website'])) {
        die("Spam detected.");
    }

    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? 'Website Inquiry');
    $message = sanitizeInput($_POST['message'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'];

    if (empty($name) || empty($email) || empty($message)) {
        header('Location: index.php?contact=error#contacts');
        exit;
    }

    $leadManager = new LeadManager();
    if ($leadManager->addLead($name, $email, $subject, $message, $ip)) {
        header('Location: index.php?contact=success#contacts');
    } else {
        header('Location: index.php?contact=error#contacts');
    }
    exit;
} else {
    header('Location: index.php');
    exit;
}
