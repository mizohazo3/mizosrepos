<?php

include_once 'timezone_config.php';
// setup_basket_db.php - Standalone script to create the user_basket table

ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'api/db.php'; // Database connection

echo "<h1>Setting up User Basket Table</h1>";

// Create user_basket table if it doesn't exist
$sql_create_basket_table = "
CREATE TABLE IF NOT EXISTS user_basket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL,
    item_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255) NULL,
    added_time DATETIME NOT NULL,
    INDEX (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sql_create_basket_table);
    echo "<p style='color: green;'>User basket table created successfully.</p>";
    
    // Check if table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_basket'");
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<p>Table structure:</p>";
        $stmt = $pdo->prepare("DESCRIBE user_basket");
        $stmt->execute();
        $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        echo "<p style='color: red;'>Table was not created successfully.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error creating user basket table: " . $e->getMessage() . "</p>";
}

echo "<p><a href='marketplace.php'>Return to Marketplace</a></p>";
?> 