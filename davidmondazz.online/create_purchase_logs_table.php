<?php
// Database configuration
$host = 'localhost';
$dbname = 'mcgkxyz_timer_app';
$username = 'mcgkxyz_masterpop';
$password = 'aA0109587045';
$db_charset = 'utf8mb4';

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$db_charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Check if the purchase_logs table already exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'purchase_logs'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the purchase_logs table
        $pdo->exec("CREATE TABLE purchase_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            item_name_snapshot VARCHAR(255) NOT NULL,
            price_paid DECIMAL(10,2) NOT NULL,
            purchase_time DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "Table 'purchase_logs' created successfully.";
    } else {
        echo "Table 'purchase_logs' already exists.";
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 