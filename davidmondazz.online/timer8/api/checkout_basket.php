<?php

include_once '../timezone_config.php';
// api/checkout_basket.php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'db.php'; // Database connection
header('Content-Type: application/json');

// Logging (optional but recommended)
$log_file = __DIR__ . '/../logs/checkout_api.log';
function log_checkout($message, $log_file) { file_put_contents($log_file, date('[Y-m-d H:i:s] ').$message."\n", FILE_APPEND); }

$response = ['status' => 'error', 'message' => 'Invalid checkout request.'];
$input = json_decode(file_get_contents('php://input'), true);
$item_ids = isset($input['item_ids']) ? $input['item_ids'] : null;

if (!$item_ids || !is_array($item_ids) || empty($item_ids)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid item IDs.']);
    exit;
}

$pdo->beginTransaction();
log_checkout("Attempting checkout for items: " . implode(", ", $item_ids), $log_file);

try {
    // 1. Calculate Current Balance Dynamically
    $current_balance = calculateDynamicBalance($pdo);
    log_checkout("Current dynamic balance calculated: $current_balance", $log_file);

    // 2. Get all item details and calculate total cost
    $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
    $stmt_items = $pdo->prepare("SELECT id, name, price, is_active FROM marketplace_items WHERE id IN ($placeholders)");
    $stmt_items->execute($item_ids);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 3. Validate all items exist and are active
    if (count($items) !== count($item_ids)) {
        throw new Exception("One or more items were not found or are no longer available.", 404);
    }

    // Check that all items are active
    foreach ($items as $item) {
        if (!$item['is_active']) {
            throw new Exception("Item '{$item['name']}' is no longer available for purchase.", 400);
        }
    }

    // 4. Calculate total cost
    $total_cost = 0;
    foreach ($items as $item) {
        $total_cost += (float)$item['price'];
    }
    log_checkout("Total cost calculated: $total_cost", $log_file);

    // 5. Check sufficient funds
    if ($current_balance < $total_cost) {
        throw new Exception("Insufficient funds. Required: $total_cost, Available: $current_balance", 400);
    }

    // 6. Process purchase for each item (Balance is no longer updated directly)
    $now_dt = new DateTime();
    $now_str = $now_dt->format('Y-m-d H:i:s');

    // Log each purchase
    $stmt_log_purchase = $pdo->prepare("
        INSERT INTO purchase_logs (item_id, item_name_snapshot, price_paid, purchase_time)
        VALUES (:item_id, :name, :price, :time)
    ");

    foreach ($items as $item) {
        $logParams = [
            ':item_id' => $item['id'],
            ':name'    => $item['name'],
            ':price'   => $item['price'],
            ':time'    => $now_str
        ];

        if (!$stmt_log_purchase->execute($logParams)) {
            throw new Exception("Failed to log purchase for item '{$item['name']}'.", 500);
        }
        log_checkout("Purchase logged for item '{$item['name']}' at price {$item['price']}", $log_file);
    }

    // 7. Commit transaction
    $pdo->commit();
    log_checkout("Checkout successful for " . count($items) . " items. Total: $total_cost", $log_file);

    // Calculate the final balance after all purchases are logged
    $final_balance = calculateDynamicBalance($pdo);
    log_checkout("Final dynamic balance after checkout: $final_balance", $log_file);

    // Prepare successful response
    $response = [
        'status'      => 'success',
        'message'     => "Successfully purchased " . count($items) . " items for " . number_format($total_cost, 2) . "!",
        'new_balance' => $final_balance, // Return the dynamically calculated balance
        'items_count' => count($items),
        'total_cost'  => $total_cost
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        log_checkout("Checkout FAILED. Rolled back transaction. Reason: ".$e->getMessage(), $log_file);
    }
    $error_code = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($error_code);
    $response['message'] = 'Checkout failed: ' . $e->getMessage();
}

echo json_encode($response);
?> 