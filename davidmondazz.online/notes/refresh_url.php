<?php
session_start();
include '../checkSession.php';
include 'db.php';
include '../func.php';
include 'functions.php';

header('Content-Type: application/json');

if (!isset($_POST['url']) || empty($_POST['url'])) {
    echo json_encode(['success' => false, 'error' => 'URL is required']);
    exit;
}

$url = $_POST['url'];

// Make sure URL has http:// or https://
if (strpos($url, 'http') !== 0) {
    $url = 'https://' . $url;
}

// Force refresh the URL title
$newTitle = refreshUrlTitle($url);

if (empty($newTitle)) {
    $newTitle = $url; // If no title found, use the URL itself
}

echo json_encode([
    'success' => true,
    'url' => $url,
    'title' => $newTitle
]); 