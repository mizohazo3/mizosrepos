<?php

include_once '../timezone_config.php';
// Test file to debug the update item price functionality

// Include database connection
require_once 'db.php';

echo "Starting test...<br>";

try {
    // Get the first item from the marketplace
    $stmt = $pdo->query("SELECT id, name, price FROM marketplace_items LIMIT 1");
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo "No items found in the marketplace_items table.<br>";
        exit;
    }
    
    echo "Found item: " . htmlspecialchars($item['name']) . " with ID " . $item['id'] . 
         " and price $" . $item['price'] . "<br>";
    
    // Calculate a new price (increase by 0.05)
    $newPrice = floatval($item['price']) + 0.05;
    
    // Update the price
    $updateStmt = $pdo->prepare("UPDATE marketplace_items SET price = ?, updated_at = NOW() WHERE id = ?");
    $result = $updateStmt->execute([$newPrice, $item['id']]);
    
    if ($result) {
        echo "Update successful! New price: $" . $newPrice . "<br>";
        
        // Verify the update
        $verifyStmt = $pdo->prepare("SELECT price FROM marketplace_items WHERE id = ?");
        $verifyStmt->execute([$item['id']]);
        $updatedItem = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Verified price in database: $" . $updatedItem['price'] . "<br>";
    } else {
        echo "Update failed!<br>";
        print_r($updateStmt->errorInfo());
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

echo "Test completed.";
?> 