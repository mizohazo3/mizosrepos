<?php

include_once '../timezone_config.php';
// api/get_purchases.php - Get recent purchases

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'db.php'; // Database connection
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Failed to load purchases.'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5; // Default to 5 most recent

// Limit the maximum number that can be requested
if ($limit > 20) $limit = 20;

try {
    // Get recent purchases with item details
    $stmt = $pdo->prepare("
        SELECT 
            pl.id, 
            pl.item_id, 
            pl.item_name_snapshot as item_name, 
            pl.price_paid, 
            pl.purchase_time,
            mi.image_url
        FROM 
            purchase_logs pl
        LEFT JOIN 
            marketplace_items mi ON pl.item_id = mi.id
        ORDER BY 
            pl.purchase_time DESC
        LIMIT :limit
    ");
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format timestamps for display
    foreach ($purchases as &$purchase) {
        // Convert purchase_time to timestamp for JavaScript
        $purchase['timestamp'] = strtotime($purchase['purchase_time']) * 1000;
        
        // Format for readability
        $purchase['formatted_time'] = date('M j, Y g:i A', strtotime($purchase['purchase_time']));
    }
    
    $response = [
        'status' => 'success',
        'purchases' => $purchases,
        'count' => count($purchases)
    ];
    
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error loading purchases: ' . $e->getMessage();
}

echo json_encode($response);
?> 