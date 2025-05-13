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

    // Pin the New Tracker By AI timer
    $stmt = $pdo->prepare("UPDATE timers SET is_pinned = 1 WHERE name = ?");
    $stmt->execute(['New Tracker By AI']);
    
    // Check if the update was successful
    $rowCount = $stmt->rowCount();
    echo "Updated $rowCount timer(s).\n";

    // Show current pinned status
    $stmt = $pdo->query("SELECT id, name, is_pinned FROM timers WHERE name = 'New Tracker By AI'");
    $timer = $stmt->fetch();
    
    if ($timer) {
        echo "Timer '{$timer['name']}' (ID: {$timer['id']}) is_pinned: {$timer['is_pinned']}\n";
    } else {
        echo "Timer 'New Tracker By AI' not found.\n";
    }

} catch (PDOException $e) {
} 