<?php

include_once 'timezone_config.php';
header('Content-Type: text/html');

// Connect to the database
$host = 'localhost';      // Or your DB host
$dbname = 'mcgkxyz_timer_app';    // Your database name
$username = 'mcgkxyz_masterpop';       // Your DB username
$password = 'aA0109587045';           // Your DB password
$db_charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$db_charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "<h1>Pin All Timers Tool</h1>";
    
    // Get current timer statuses before update
    $stmt = $pdo->query("SELECT id, name, is_pinned FROM timers ORDER BY id ASC");
    $timers = $stmt->fetchAll();
    
    echo "<h2>Timer Status Before Update</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>is_pinned</th></tr>";
    
    foreach ($timers as $timer) {
        echo "<tr>";
        echo "<td>{$timer['id']}</td>";
        echo "<td>{$timer['name']}</td>";
        echo "<td>{$timer['is_pinned']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Update all timers to be pinned
    $stmt = $pdo->prepare("UPDATE timers SET is_pinned = 1");
    $stmt->execute();
    
    $rowCount = $stmt->rowCount();
    echo "<p>Updated $rowCount timers to pinned status.</p>";
    
    // Get updated timer statuses
    $stmt = $pdo->query("SELECT id, name, is_pinned FROM timers ORDER BY id ASC");
    $updatedTimers = $stmt->fetchAll();
    
    echo "<h2>Timer Status After Update</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>is_pinned</th></tr>";
    
    foreach ($updatedTimers as $timer) {
        echo "<tr>";
        echo "<td>{$timer['id']}</td>";
        echo "<td>{$timer['name']}</td>";
        echo "<td>{$timer['is_pinned']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p>All timers have been pinned. <a href='index.php'>Return to Timer System</a></p>";
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
} 