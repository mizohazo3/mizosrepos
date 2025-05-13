<?php

include_once 'timezone_config.php';
// Connect to the database
$host = 'localhost';
$dbname = 'timer_app';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM timers LIKE 'is_pinned'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        // Add the column if it doesn't exist
        $pdo->exec("ALTER TABLE timers ADD COLUMN is_pinned TINYINT(1) NOT NULL DEFAULT 0");
        $pdo->exec("CREATE INDEX idx_is_pinned ON timers(is_pinned)");
        echo "Added is_pinned column and index successfully.\n";
    } else {
        echo "is_pinned column already exists.\n";
    }

    // Update any existing pinned timers to ensure proper values
    $pdo->exec("UPDATE timers SET is_pinned = IF(is_pinned = '1' OR is_pinned = 1, 1, 0)");
    echo "Normalized is_pinned values.\n";

    echo "Done!";

} catch (PDOException $e) {
} 