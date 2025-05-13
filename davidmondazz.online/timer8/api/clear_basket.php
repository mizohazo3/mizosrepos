<?php

include_once '../timezone_config.php';
// api/clear_basket.php

// Start the session right at the beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'db.php'; // Database connection
header('Content-Type: application/json');

// Enable CORS if needed
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: POST');
// header('Access-Control-Allow-Headers: Content-Type');

$response = ['status' => 'error', 'message' => 'Failed to clear basket.'];
$input = json_decode(file_get_contents('php://input'), true);
$user_token = isset($input['user_token']) ? $input['user_token'] : null;

try {
    // Get the session ID as fallback
    $session_id = session_id();
    
    // If no user token was provided, use session_id as fallback
    if (empty($user_token)) {
        $user_token = $session_id;
    }
    
    if (empty($user_token)) {
        throw new Exception("No valid user token or session ID found");
    }
    
    // Delete basket items for this user token
    $stmt = $pdo->prepare("DELETE FROM user_basket WHERE session_id = :user_token");
    $stmt->execute([':user_token' => $user_token]);

    $response = [
        'status' => 'success',
        'message' => 'Basket cleared successfully.',
        'user_token' => $user_token // Return the token used
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error clearing basket: ' . $e->getMessage();
}

echo json_encode($response);
?> 