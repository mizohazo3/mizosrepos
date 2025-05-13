<?php

include_once '../timezone_config.php';
// api/get_note_details.php - Endpoint to get details of a specific note payment

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Required files
require_once 'db.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Default response
$response = [
    'status' => 'error',
    'message' => 'An unexpected error occurred.'
];

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Only GET requests are allowed.';
    http_response_code(405);
    echo json_encode($response);
}

// Check for required ID parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'Missing or invalid note ID.';
    http_response_code(400);
    echo json_encode($response);
}

$noteId = intval($_GET['id']);

try {
    // Get note details
    $stmt = $pdo->prepare("
        SELECT * FROM on_the_note 
        WHERE id = ? AND is_paid = 1
    ");
    $stmt->execute([$noteId]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$note) {
        $response['message'] = 'Note not found or not paid yet.';
        http_response_code(404);
        echo json_encode($response);
    }
    
    // Add debug info
    
    // Parse items list
    if (isset($note['items_list'])) {
        // Keep the items_list as JSON string, we'll parse it in JavaScript
    }
    
    $response = [
        'status' => 'success',
        'note' => $note
    ];

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    http_response_code(500);
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    http_response_code(500);
}

// Return JSON response
echo json_encode($response); 