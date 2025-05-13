<?php
// api/get_session_info.php - Diagnostic endpoint for troubleshooting session issues

// Start the session right at the beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once '../timezone_config.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'db.php'; // Database connection
header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_name' => session_name(),
    'cookie_params' => session_get_cookie_params(),
    'session_started' => (session_status() === PHP_SESSION_ACTIVE),
    'session_data' => []
];

// Get a count of basket items for this session
try {
    if ($response['session_id']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as item_count FROM user_basket WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $response['session_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['basket_item_count'] = (int)$row['item_count'];
        
        // Get all items for this session
        $stmt = $pdo->prepare("
            SELECT id, session_id, item_id, item_name, item_price, added_time
            FROM user_basket 
            WHERE session_id = :session_id
            ORDER BY added_time DESC
        ");
        $stmt->execute([':session_id' => $response['session_id']]);
        $response['basket_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $response['db_error'] = $e->getMessage();
}

// Include session variables (safely)
if (isset($_SESSION) && is_array($_SESSION)) {
    foreach ($_SESSION as $key => $value) {
        // Only include non-sensitive session data
        if (!in_array($key, ['password', 'token', 'auth', 'secret'])) {
            // Convert objects to string to prevent circular references
            if (is_object($value)) {
                $response['session_data'][$key] = 'Object: ' . get_class($value);
            } else if (is_array($value)) {
                $response['session_data'][$key] = 'Array with ' . count($value) . ' items';
            } else {
                $response['session_data'][$key] = $value;
            }
        }
    }
}

echo json_encode($response);
?> 