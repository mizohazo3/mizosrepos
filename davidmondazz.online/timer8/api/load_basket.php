<?php
ob_start(); // Start output buffering

// Start the session right at the beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once '../timezone_config.php';
// api/load_basket.php

ini_set('display_errors', 0); // Keep errors hidden from user output
ini_set('log_errors', 1);     // Log errors to the server log
error_reporting(E_ALL);

require_once 'db.php'; // Database connection
header('Content-Type: application/json');

// Enable CORS if needed (uncomment if frontend is on a different domain/port)
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST');
// header('Access-Control-Allow-Headers: Content-Type');

$response = ['status' => 'error', 'message' => 'Failed to load basket.'];
$items = [];
$total = 0;
$item_count = 0;

// Get user token from query parameter or fallback to session ID
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : null;
$session_id = session_id();

// If no user token was provided, use session_id as fallback
if (empty($user_token)) {
    $user_token = $session_id;
}

try {
    if ($user_token) { 
        // Fetch all items for the current user token (stored in session_id column)
        $stmt_items = $pdo->prepare("
            SELECT item_id as id, item_name as name, item_price as price, image_url
            FROM user_basket
            WHERE session_id = :user_token
            ORDER BY added_time ASC
        ");
        $stmt_items->execute([':user_token' => $user_token]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total and count
        foreach ($items as $item) {
            $total += (float)$item['price'];
        }
        $item_count = count($items);

        $response = [
            'status' => 'success',
            'items' => $items,
            'total' => $total,
            'item_count' => $item_count,
            'user_token' => $user_token // Return the token used
        ];

    } else {
        // No user token or session ID could be obtained
        $response = [
            'status' => 'success', // Still success, just an empty basket
            'items' => [],
            'total' => 0,
            'item_count' => 0,
            'message' => 'No active user token found.',
            'user_token' => null
        ];
        error_log('Could not retrieve user token or session ID in load_basket.php');
    }

} catch (Exception $e) {
    error_log('Error loading basket: ' . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'Error loading basket. Please check server logs.';
}

ob_end_clean(); // Clear any previous output buffer
header('Content-Type: application/json'); // Ensure header is set *after* clearing buffer
echo json_encode($response);
exit; // Ensure script stops here
?>
