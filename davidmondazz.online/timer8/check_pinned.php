<?php

include_once 'timezone_config.php';
// check_pinned.php - Script to check and fix pinned timer status

// Connect directly to the database using the credentials from api/db.php
try {
    $host = 'localhost';      
    $dbname = 'mcgkxyz_timer_app';    
    $username = 'mcgkxyz_masterpop';       
    $password = 'aA0109587045';
    $db_charset = 'utf8mb4';
    
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$db_charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
}

// Get all timers with is_pinned status
$stmt = $pdo->query("SELECT id, name, is_pinned FROM timers");
$timers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Timer Pinned Status</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>is_pinned Value</th><th>Type</th></tr>";

foreach($timers as $timer) {
    echo "<tr>";
    echo "<td>{$timer['id']}</td>";
    echo "<td>{$timer['name']}</td>";
    echo "<td>{$timer['is_pinned']}</td>";
    echo "<td>" . gettype($timer['is_pinned']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Fix any string values to be proper integers
$stmt = $pdo->prepare("UPDATE timers SET is_pinned = :pin_value WHERE id = :id");

foreach($timers as $timer) {
    // If is_pinned is a string or not 0 or 1, convert it
    if (!is_int($timer['is_pinned']) || ($timer['is_pinned'] != 0 && $timer['is_pinned'] != 1)) {
        $newValue = $timer['is_pinned'] ? 1 : 0;
        $stmt->execute([
            ':pin_value' => $newValue,
            ':id' => $timer['id']
        ]);
        echo "<p>Fixed timer {$timer['id']} ({$timer['name']}): Changed is_pinned from '{$timer['is_pinned']}' to '$newValue'</p>";
    }
}

// Ensure the column type is correct
try {
    // Check column type
    $stmt = $pdo->query("SHOW COLUMNS FROM timers LIKE 'is_pinned'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>is_pinned column type: {$column['Type']}</p>";
    
    // If not TINYINT, alter it
    if (strpos($column['Type'], 'tinyint') === false) {
        $pdo->exec("ALTER TABLE timers MODIFY COLUMN is_pinned TINYINT(1) DEFAULT 0");
        echo "<p>Column type changed to TINYINT(1)</p>";
    }
} catch (Exception $e) {
    echo "<p>Error checking/fixing column type: " . $e->getMessage() . "</p>";
}

echo "<p>All done!</p>"; 