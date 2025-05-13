<?php
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

    // Fetch current balance to display on the page
    $stmt_balance = $pdo->prepare("SELECT bank_balance FROM user_progress WHERE id = 1");
    $stmt_balance->execute();
    $balance_row = $stmt_balance->fetch();
    $current_balance = $balance_row ? (float)$balance_row['bank_balance'] : 0.0;


    $response = [
        'status' => 'success',
        'items' => $items,
        'current_balance' => $current_balance
    ];

} catch (Exception $e) {
    http_response_code(500);
    error_log("Marketplace API Error: " . $e->getMessage());
    $response['message'] = 'Error retrieving marketplace items: ' . $e->getMessage();
}

echo json_encode($response);
?>