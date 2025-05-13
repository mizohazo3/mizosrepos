<?php

include_once '../timezone_config.php';
// api/batch_purchase.php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'db.php'; // Database connection
header('Content-Type: application/json');

// Enable CORS if needed
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: POST');
// header('Access-Control-Allow-Headers: Content-Type');

// Logging
$log_file = __DIR__ . '/../logs/purchase_api.log';
function log_purchase($message, $log_file) { 
    file_put_contents($log_file, date('[Y-m-d H:i:s] ').$message."\n", FILE_APPEND); 
}

$response = ['status' => 'error', 'message' => 'Invalid batch purchase request.'];
$input = json_decode(file_get_contents('php://input'), true);
$item_ids = isset($input['item_ids']) ? $input['item_ids'] : null;

if (!$item_ids || !is_array($item_ids) || empty($item_ids)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid item IDs.']);
    exit;
}

$pdo->beginTransaction();
log_purchase("Attempting batch purchase for items: " . implode(', ', $item_ids), $log_file);

try {
    $total_price = 0;
    $item_details = [];
    
    // 1. Get all item details and validate all items
    foreach ($item_ids as $item_id) {
        $stmt_item = $pdo->prepare("SELECT id, name, price, is_active, stock FROM marketplace_items WHERE id = :id");
        $stmt_item->execute([':id' => $item_id]);
        $item = $stmt_item->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            throw new Exception("Item #$item_id not found.", 404);
        }
        
        if (!$item['is_active']) {
            throw new Exception("Item '{$item['name']}' is currently not available for purchase.", 400);
        }
        
        // Add stock check here if implemented
        // if ($item['stock'] == 0) {
        //     throw new Exception("Item '{$item['name']}' is out of stock.", 400);
        // }
        
        $total_price += (float)$item['price'];
        $item_details[] = $item;
    }
    
    log_purchase("Total price for batch purchase: $total_price", $log_file);
    
    // 2. Calculate Current Balance Dynamically
    $current_balance = calculateDynamicBalance($pdo);
    log_purchase("Current dynamic balance calculated: $current_balance", $log_file);
    
    // 3. Check Funds
    if ($current_balance < $total_price) {
        throw new Exception("Insufficient funds. Required: $total_price, Available: $current_balance", 400);
    }
    
    // 4. Log each purchase (Balance is no longer updated directly)
    $now_dt = new DateTime();
    $now_str = $now_dt->format('Y-m-d H:i:s');
    $stmt_log_purchase = $pdo->prepare("
        INSERT INTO purchase_logs (item_id, item_name_snapshot, price_paid, purchase_time)
        VALUES (:item_id, :name, :price, :time)
    ");
    
    foreach ($item_details as $item) {
        $logParams = [
            ':item_id' => $item['id'],
            ':name'    => $item['name'],
            ':price'   => $item['price'],
            ':time'    => $now_str
        ];
        
        if (!$stmt_log_purchase->execute($logParams)) {
            throw new Exception("Failed to log purchase for item '{$item['name']}'.", 500);
        }
        
        // Optional: Update Stock
        // if ($item['stock'] > 0) {
        //     $stmt_update_stock = $pdo->prepare("UPDATE marketplace_items SET stock = stock - 1 WHERE id = :id AND stock > 0");
        //     if (!$stmt_update_stock->execute([':id' => $item['id']])) {
        //         log_purchase("WARNING: Failed to decrement stock for item {$item['id']}", $log_file);
        //     }
        // }
    }
    
    // 6. Commit
    $pdo->commit();
    log_purchase("Batch purchase successful. Transaction committed.", $log_file);
    
    // Calculate the final balance after all purchases are logged
    $final_balance = calculateDynamicBalance($pdo);
    log_purchase("Final dynamic balance after batch purchase: $final_balance", $log_file);

    $response = [
        'status'      => 'success',
        'message'     => count($item_details) . " items purchased successfully!",
        'new_balance' => $final_balance, // Return the dynamically calculated balance
        'items'       => array_map(function($item) { return ['id' => $item['id'], 'name' => $item['name']]; }, $item_details)
    ];
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        log_purchase("Batch purchase FAILED. Rolled back transaction. Reason: ".$e->getMessage(), $log_file);
    }
    
    $error_code = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($error_code);
    $response['message'] = 'Purchase failed: ' . $e->getMessage();
}

echo json_encode($response);
?> 