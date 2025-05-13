<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to timer_app database
try {
    $timerDbConfig = [
        'host' => 'localhost',
        'dbname' => 'mcgkxyz_timer_app',
        'username' => 'mcgkxyz_masterpop',
        'password' => 'aA0109587045'
    ];
    
    $timerDb = new PDO(
        "mysql:host={$timerDbConfig['host']};dbname={$timerDbConfig['dbname']};charset=utf8mb4",
        $timerDbConfig['username'],
        $timerDbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Get table structure
    $stmt = $timerDb->query("DESCRIBE purchase_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>purchase_logs Table Structure</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Get a few sample records if available
    $stmt = $timerDb->query("SELECT * FROM purchase_logs LIMIT 5");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($records) > 0) {
        echo "<h2>Sample Records</h2>";
        echo "<table border='1'>";
        
        // Table header
        echo "<tr>";
        foreach (array_keys($records[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        // Table data
        foreach ($records as $record) {
            echo "<tr>";
            foreach ($record as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No records found in purchase_logs table.</p>";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>