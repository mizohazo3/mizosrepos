<?php

include_once '../timezone_config.php';
/**
 * API Endpoint: Update Item Price
 * 
 * Updates the price of a marketplace item.
 * 
 * Method: POST
 * Request body:
 *   - item_id: int (required) - ID of the item to update
 *   - new_price: float (required) - New price for the item
 * 
 * Response:
 *   - status: 'success' or 'error'
 *   - message: success/error message
 */

header('Content-Type: application/json');

// Include database connection
require_once 'db.php';

// Check for POST request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. Must be POST.'
    ]);
    exit;
}

// Get JSON data from request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Debug - log the received data

// Validate required fields
if (!isset($data['item_id']) || !isset($data['new_price'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters: item_id and new_price are required.'
    ]);
    exit;
}

// Sanitize and validate input
$item_id = filter_var($data['item_id'], FILTER_VALIDATE_INT);
$new_price = filter_var($data['new_price'], FILTER_VALIDATE_FLOAT);

if ($item_id === false) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid item ID format.'
    ]);
    exit;
}

if ($new_price === false || $new_price < 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid price format. Price must be a positive number.'
    ]);
    exit;
}

try {
    // First, check if the item exists
    $check_stmt = $pdo->prepare("SELECT id FROM marketplace_items WHERE id = ?");
    $check_stmt->execute([$item_id]);
    
    if ($check_stmt->rowCount() === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Item not found.'
        ]);
        exit;
    }
    
    // Update the item price - removed updated_at field as it might not exist
    $stmt = $pdo->prepare("UPDATE marketplace_items SET price = ? WHERE id = ?");
    $result = $stmt->execute([$new_price, $item_id]);
    
    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Item price updated successfully.'
        ]);
        
        // Debug log
    } else {
        $error = $stmt->errorInfo();
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update item price.'
        ]);
    }
} catch (PDOException $e) {
    // Log the error but don't expose details to the client
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred while updating item price.'
    ]);
} 