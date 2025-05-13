<?php

include_once 'timezone_config.php';
header('Content-Type: text/html');

echo "<h1>API Data Diagnostic Tool</h1>";

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
    
    // Check if get_data.php exists
    $get_data_path = 'api/get_data.php';
    echo "<h2>Checking API Files</h2>";
    if (file_exists($get_data_path)) {
        echo "<p>✅ {$get_data_path} file exists</p>";
        // Show file content
        echo "<pre>" . htmlspecialchars(file_get_contents($get_data_path)) . "</pre>";
    } else {
        echo "<p>❌ {$get_data_path} does not exist!</p>";
    }
    
    // Check database tables
    echo "<h2>Database Tables</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Found " . count($tables) . " tables:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";
    
    // Check timers table
    echo "<h2>Timers in Database</h2>";
    if (in_array('timers', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM timers");
        $timerCount = $stmt->fetchColumn();
        echo "<p>Found {$timerCount} timers in the database.</p>";
        
        if ($timerCount > 0) {
            $stmt = $pdo->query("SELECT * FROM timers ORDER BY is_pinned DESC, id DESC LIMIT 10");
            $timers = $stmt->fetchAll();
            
            echo "<table border='1'>";
            echo "<tr>";
            foreach (array_keys($timers[0]) as $column) {
                echo "<th>{$column}</th>";
            }
            echo "</tr>";
            
            foreach ($timers as $timer) {
                echo "<tr>";
                foreach ($timer as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p>❌ 'timers' table does not exist!</p>";
    }
    
    // Output what get_data.php would return
    echo "<h2>Simulated API Response (get_data.php)</h2>";
    
    // Get bank balance
    $bankBalance = 0;
    $stmt = $pdo->query("SELECT bank_balance FROM user_progress WHERE id = 1");
    if ($stmt->rowCount() > 0) {
        $bankBalance = (float)$stmt->fetchColumn();
    }
    
    // Get difficulty multiplier
    $difficultyMultiplier = 1.0;
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'difficulty_multiplier'");
    if ($stmt->rowCount() > 0) {
        $difficultyMultiplier = (float)$stmt->fetchColumn();
    }
    
    // Get levels config
    $levelsConfig = [];
    $stmt = $pdo->query("SELECT level, hours_required, rank_name, reward_rate_per_hour FROM levels ORDER BY level ASC");
    if ($stmt->rowCount() > 0) {
        $levels = $stmt->fetchAll();
        foreach ($levels as $level) {
            $levelsConfig[$level['level']] = $level;
        }
    }
    
    // Get timers
    $timers = [];
    $stmt = $pdo->query("SELECT * FROM timers ORDER BY is_pinned DESC, id DESC");
    if ($stmt->rowCount() > 0) {
        $timers = $stmt->fetchAll();
    }
    
    $apiResponse = [
        'status' => 'success',
        'bank_balance' => $bankBalance,
        'difficulty_multiplier' => $difficultyMultiplier,
        'levels_config' => $levelsConfig,
        'timers' => $timers
    ];
    
    echo "<pre>" . json_encode($apiResponse, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (PDOException $e) {
    echo "<p>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} 