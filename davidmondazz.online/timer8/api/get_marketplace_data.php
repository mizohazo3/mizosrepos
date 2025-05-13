<?php

include_once '../timezone_config.php';
// api/get_marketplace_data.php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Failed to retrieve marketplace data.'];

try {
    // Fetch only active items, ordered perhaps by price or name
    $stmt = $pdo->prepare("
        SELECT id, name, description, price, image_url, stock
        FROM marketplace_items
        WHERE is_active = 1
        ORDER BY price ASC, name ASC
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch current balance using the shared dynamic balance function from db.php
    // This function correctly calculates income - purchases - paid notes.
    $current_balance = calculateDynamicBalance($pdo);


    $response = [
        'status' => 'success',
        'items' => $items,
        'current_balance' => number_format($current_balance, 2, '.', '')
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error retrieving marketplace items: ' . $e->getMessage();
}

echo json_encode($response);
?>