<?php

include_once '../timezone_config.php';
// api/refund_purchase.php - Process a refund

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'db.php'; // Database connection
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = ['status' => 'error', 'message' => 'Failed to process refund.'];

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $response['message'] = 'Only POST method is allowed';
    echo json_encode($response);
    exit;
}

// Get request body
$input_json = file_get_contents('php://input');
$input = json_decode($input_json, true);
$purchase_id = isset($input['purchase_id']) ? intval($input['purchase_id']) : 0;

if (!$purchase_id) {
    http_response_code(400);
    $response['message'] = 'Missing or invalid purchase ID';
    echo json_encode($response);
    exit;
}


try {
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Get purchase details
    $stmt = $pdo->prepare("
        SELECT id, item_id, item_name_snapshot, price_paid, purchase_time
        FROM purchase_logs
        WHERE id = :id
    ");
    $stmt->execute([':id' => $purchase_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$purchase) {
        http_response_code(404);
        $response['message'] = "Purchase not found with ID: $purchase_id";
        echo json_encode($response);
        $pdo->rollBack();
        exit;
    }
    
    // Check if refund is allowed (e.g., within 24 hours)
    $purchase_time = strtotime($purchase['purchase_time']);
    $current_time = time();
    $time_diff = $current_time - $purchase_time;
    $refund_window = 24 * 60 * 60; // 24 hours in seconds
    $hours_left = ($refund_window - $time_diff) / 3600;
    
    if ($time_diff > $refund_window) {
        http_response_code(400);
        $response['message'] = 'Refund period has expired (24 hour limit)';
        echo json_encode($response);
        $pdo->rollBack();
        exit;
    }
    
    // 2. Delete the purchase log (This effectively refunds the amount as it's removed from the calculation)
    $refund_amount = $purchase['price_paid']; // Keep for response
    
    $stmt = $pdo->prepare("DELETE FROM purchase_logs WHERE id = :id");
    $stmt->execute([':id' => $purchase_id]);
    $rows_deleted = $stmt->rowCount();
    
    if ($rows_deleted !== 1) {
        throw new Exception("Failed to delete purchase log, rows affected: $rows_deleted");
    }
    
    // 3. Calculate the new balance after deleting the log
    $new_balance = calculateDynamicBalance($pdo);
    
    // 4. Commit transaction
    $pdo->commit();
    
    $response = [
        'status' => 'success',
        'message' => 'Refund processed successfully',
        'refunded_amount' => $refund_amount,
        'item_name' => $purchase['item_name_snapshot'],
        'new_balance' => $new_balance,
        'purchase_id' => $purchase_id
    ];
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    $response['message'] = 'Error processing refund.';
}

echo json_encode($response);
?> 