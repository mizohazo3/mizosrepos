<?php

include_once 'timezone_config.php';
// Set error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration - use the same as db.php
$host = 'localhost';
$dbname = 'timer8_online';
$username = 'root';
$password = '';
$db_charset = 'utf8mb4';

echo "<h1>Database Connection Test</h1>";

try {
    // Try to connect to database
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
    
    echo "<p style='color: green;'>Database connection successful!</p>";
    
    // Test query to verify database structure
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables in database:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
    
    // Check if database exists
    try {
        $pdo = new PDO(
            "mysql:host=$host;charset=$db_charset",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h2>Available databases:</h2>";
        echo "<ul>";
        foreach ($databases as $database) {
            echo "<li>$database</li>";
        }
        echo "</ul>";
        
        if (!in_array($dbname, $databases)) {
            echo "<p>The database '$dbname' does not exist. You may need to create it or import it from SQL file.</p>";
        }
        
    } catch (PDOException $e2) {
        echo "<p>Could not connect to MySQL server: " . $e2->getMessage() . "</p>";
    }
}
?> 