<?php

include_once 'timezone_config.php';
// Set error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'timer8_online';

echo "<h1>Setting up Timer8 Online Database</h1>";

try {
    // Connect to MySQL server without selecting a database
    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p>Connected to MySQL server successfully.</p>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $databaseExists = $stmt->rowCount() > 0;
    
    if ($databaseExists) {
        echo "<p>Database '$dbname' already exists. Do you want to drop it and recreate? <a href='?drop=1'>Yes</a> / <a href='test_db_connection.php'>No</a></p>";
        
        if (isset($_GET['drop'])) {
            $pdo->exec("DROP DATABASE `$dbname`");
            echo "<p>Database '$dbname' dropped successfully.</p>";
            $databaseExists = false;
        } else {
            echo "<p>Skipping database creation. <a href='test_db_connection.php'>Test connection</a></p>";
            exit;
        }
    }
    
    if (!$databaseExists) {
        // Create database
        $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p>Database '$dbname' created successfully.</p>";
        
        // Select the database
        $pdo->exec("USE `$dbname`");
        
        // Read SQL file
        $sqlFile = file_get_contents('mcgkxyz_timer_app.sql');
        
        if (!$sqlFile) {
            echo "<p style='color: red;'>Error: Could not read SQL file.</p>";
            exit;
        }
        
        // Replace database name in SQL if needed
        $sqlFile = str_replace('mcgkxyz_timer_app', $dbname, $sqlFile);
        
        // Split SQL file into queries
        $queries = explode(';', $sqlFile);
        
        // Execute each query
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                try {
                    $pdo->exec($query);
                } catch (PDOException $e) {
                    echo "<p style='color: orange;'>Warning on query: " . htmlspecialchars($e->getMessage()) . "</p>";
                    // Continue with other queries even if one fails
                }
            }
        }
        
        echo "<p style='color: green;'>Database structure imported successfully!</p>";
        
        // Insert initial data for levels table if it's empty
        $stmt = $pdo->query("SELECT COUNT(*) FROM levels");
        $levelCount = $stmt->fetchColumn();
        
        if ($levelCount == 0) {
            $levelsData = [
                [1, 0.0000, 'Beginner', 0.0100],
                [2, 1.0000, 'Novice', 0.0200],
                [3, 5.0000, 'Apprentice', 0.0300],
                [4, 20.0000, 'Practitioner', 0.0400],
                [5, 50.0000, 'Expert', 0.0500],
                [6, 100.0000, 'Master', 0.0600],
                [7, 200.0000, 'Grandmaster', 0.0700],
                [8, 500.0000, 'Legend', 0.0800]
            ];
            
            $stmt = $pdo->prepare("INSERT INTO levels (level, hours_required, rank_name, reward_rate_per_hour) VALUES (?, ?, ?, ?)");
            
            foreach ($levelsData as $level) {
                $stmt->execute($level);
            }
            
            echo "<p>Inserted initial level data.</p>";
        }
        
        // Initialize user_progress
        $stmt = $pdo->query("SELECT COUNT(*) FROM user_progress");
        $progressCount = $stmt->fetchColumn();
        
        if ($progressCount == 0) {
            // Insert only the ID, as bank_balance is now calculated dynamically
            $pdo->exec("INSERT INTO user_progress (id) VALUES (1)");
            echo "<p>Initialized user progress (ID only).</p>";
        }
    }
    
    echo "<p>Setup completed! <a href='test_db_connection.php'>Test connection</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 