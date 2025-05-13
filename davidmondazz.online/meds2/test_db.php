<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

echo "<h2>Database Connection Test</h2>";

try {
    // Test query to check connection and table structure
    $result = $con->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Database connection successful!</p>";
    echo "<p>Tables in database:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // Test medtrack table structure
    if (in_array('medtrack', $tables)) {
        echo "<h3>medtrack Table Structure:</h3>";
        $columns = $con->query("DESCRIBE medtrack");
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for recent records
        echo "<h3>Recent Records in medtrack:</h3>";
        $records = $con->query("SELECT id, medname, dose_date, details FROM medtrack ORDER BY id DESC LIMIT 5");
        if ($records->rowCount() > 0) {
            echo "<table border='1'><tr><th>ID</th><th>Medication</th><th>Date</th><th>Details</th></tr>";
            while ($row = $records->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['medname']) . "</td>";
                echo "<td>" . htmlspecialchars($row['dose_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['details']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No records found in medtrack table.</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
    
    // Check if db.php exists and is accessible
    echo "<p>Checking db.php file:</p>";
    if (file_exists('db.php')) {
        echo "<p>db.php file exists. Checking content (sensitive info redacted):</p>";
        $dbContent = file_get_contents('db.php');
        // Redact sensitive information
        $dbContent = preg_replace('/([\'"])([^\'"]*)([\'"])\s*=>\s*([\'"])([^\'"]*)([\'"])/', '$1$2$3 => $4******$6', $dbContent);
        echo "<pre>" . htmlspecialchars($dbContent) . "</pre>";
    } else {
        echo "<p style='color: red;'>db.php file not found!</p>";
    }
}

// Display PHP info
echo "<h3>PHP Info:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "</p>";
?>