<?php

include_once '../timezone_config.php';
// api/save_basket.php

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

$response = ['status' => 'error', 'message' => 'Failed to save basket.'];
$input = json_decode(file_get_contents('php://input'), true);
$items = isset($input['items']) ? $input['items'] : null;
$user_token = isset($input['user_token']) ? $input['user_token'] : null;

if (!is_array($items)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid basket items format.']);
    exit;
}

try {
    // Get the user token or fallback to session_id
    $session_id = session_id();
    
    // If no user token was provided, use session_id as fallback
    if (empty($user_token)) {
        $user_token = $session_id;
    }
    
    if (empty($user_token)) {
        throw new Exception("No valid user token or session ID found");
    }
    
    // Start transaction
    $pdo->beginTransaction();

    // Delete existing basket items for this user token
    $stmt_delete = $pdo->prepare("DELETE FROM user_basket WHERE session_id = :user_token");
    $stmt_delete->execute([':user_token' => $user_token]);

    // Then insert new items
    if (count($items) > 0) {
        $stmt_insert = $pdo->prepare("
            INSERT INTO user_basket (session_id, item_id, item_name, item_price, image_url, added_time)
            VALUES (:user_token, :item_id, :item_name, :item_price, :image_url, :added_time)
        ");

        // Get current time in the configured timezone
        $now = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $stmt_insert->execute([
                ':user_token' => $user_token,
                ':item_id' => $item['id'],
                ':item_name' => $item['name'],
                ':item_price' => $item['price'],
                ':image_url' => $item['image_url'] ?? null,
                ':added_time' => $now // Use the PHP-generated timestamp
            ]);
        }
    }

    $pdo->commit();

    $response = [
        'status' => 'success',
        'message' => 'Basket saved successfully.',
        'item_count' => count($items),
        'user_token' => $user_token // Return the token used
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    $response['message'] = 'Error saving basket: ' . $e->getMessage();
}

echo json_encode($response);
?>