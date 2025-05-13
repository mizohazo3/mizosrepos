<?php

include_once '../timezone_config.php';
// api/purchase_item.php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'db.php'; // Database connection
header('Content-Type: application/json');

// Logging (optional but recommended)
$log_file = __DIR__ . '/../logs/purchase_api.log';
function log_purchase($message, $log_file) { file_put_contents($log_file, date('[Y-m-d H:i:s] ').$message."\n", FILE_APPEND); }

$response = ['status' => 'error', 'message' => 'Invalid purchase request.'];
$input = json_decode(file_get_contents('php://input'), true);
$item_id = isset($input['item_id']) ? (int)$input['item_id'] : null;

if (!$item_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing item ID.']);
    exit;
}

$pdo->beginTransaction();
log_purchase("Attempting purchase for item ID: $item_id", $log_file);

try {
    // 1. Get Item Details (Check Price, Active Status, Name, and potentially Stock)
    $stmt_item = $pdo->prepare("SELECT name, price, is_active, stock FROM marketplace_items WHERE id = :id");
    $stmt_item->execute([':id' => $item_id]);
    $item = $stmt_item->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
         throw new Exception("Item not found.", 404);
    }
    if (!$item['is_active']) {
        throw new Exception("Item is currently not available for purchase.", 400);
    }
    // Add stock check here if implemented (e.g., if ($item['stock'] == 0) {...})

    $item_price = (float)$item['price'];
    $item_name = $item['name'];
    log_purchase("Item '$item_name' found. Price: $item_price", $log_file);

    // 2. Calculate Current Balance Dynamically
    $current_balance = calculateDynamicBalance($pdo);
    log_purchase("Current dynamic balance calculated: $current_balance", $log_file);

    // 3. Check Funds
    if ($current_balance < $item_price) {
         throw new Exception("Insufficient funds. Required: $item_price, Available: $current_balance", 400);
    }

    // 4. Log the Purchase (Balance is no longer updated directly)
    $now_dt = new DateTime();
    $now_str = $now_dt->format('Y-m-d H:i:s');
    $stmt_log_purchase = $pdo->prepare("
        INSERT INTO purchase_logs (item_id, item_name_snapshot, price_paid, purchase_time)
        VALUES (:item_id, :name, :price, :time)
    ");
    $logParams = [
        ':item_id' => $item_id,
        ':name'    => $item_name, // Use the name fetched earlier
        ':price'   => $item_price,
        ':time'    => $now_str
    ];
    if (!$stmt_log_purchase->execute($logParams)) {
         throw new Exception("Failed to log purchase.", 500);
    }
     log_purchase("Purchase logged successfully. Log Params: ".json_encode($logParams), $log_file);

    // Optional: 6. Update Stock
    // if ($item['stock'] > 0) { // Check if stock is limited
    //     $stmt_update_stock = $pdo->prepare("UPDATE marketplace_items SET stock = stock - 1 WHERE id = :id AND stock > 0");
    //     if (!$stmt_update_stock->execute([':id' => $item_id])) {
    //         // Handle potential error - maybe rollback?
    //         log_purchase("WARNING: Failed to decrement stock for item $item_id", $log_file);
    //     }
    // }

    // 7. Commit
    $pdo->commit();
    log_purchase("Purchase successful. Transaction committed.", $log_file);

    // Calculate the final balance after the purchase is logged
    $final_balance = calculateDynamicBalance($pdo);
    log_purchase("Final dynamic balance after purchase: $final_balance", $log_file);

    $response = [
        'status'        => 'success',
        'message'       => "'{$item_name}' purchased successfully!",
        'new_balance'   => $final_balance, // Return the dynamically calculated balance
        'item_id'       => $item_id
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        log_purchase("Purchase FAILED. Rolled back transaction. Reason: ".$e->getMessage(), $log_file);
    }
    $error_code = $e->getCode() >= 400 ? $e->getCode() : 500; // Use exception code if it's client-side (4xx)
    http_response_code($error_code);
    $response['message'] = 'Purchase failed: ' . $e->getMessage();
    error_log("Purchase API Error: " . $e->getMessage());
}

echo json_encode($response);
?>