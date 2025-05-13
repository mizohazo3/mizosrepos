<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

echo "<h1>Timer Logs Table Structure</h1>";

// Connect to database
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Check if note column exists
    $result = $conn->query("SHOW COLUMNS FROM timer_logs LIKE 'note'");
    $noteColumnExists = $result->num_rows > 0;
    
    echo "<p>Note column exists: " . ($noteColumnExists ? "Yes" : "No") . "</p>";
    
    // Show all columns in timer_logs table
    $result = $conn->query("SHOW COLUMNS FROM timer_logs");
    
    if ($result === false) {
        echo "<p>Error: " . $conn->error . "</p>";
    } else {
        echo "<h2>Columns in timer_logs table:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // If note column doesn't exist, add it
    if (!$noteColumnExists) {
        echo "<h2>Adding note column to timer_logs table</h2>";
        $sql = "ALTER TABLE timer_logs ADD COLUMN note TEXT NULL AFTER duration";
        
        if ($conn->query($sql) === TRUE) {
            echo "<p>Column 'note' added to timer_logs table successfully</p>";
        } else {
            echo "<p>Error adding column 'note': " . $conn->error . "</p>";
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?> 