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
    
    // Check if the column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM user_progress LIKE 'USDEGP'");
    $columnExists = $stmt->fetch() !== false;
    
    if (!$columnExists) {
        // Add USDEGP column with default value 50.7
        $pdo->exec("ALTER TABLE user_progress ADD COLUMN USDEGP DECIMAL(10,2) NOT NULL DEFAULT 50.7");
        echo "USDEGP column added successfully to user_progress table.";
    } else {
        echo "USDEGP column already exists in user_progress table.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 