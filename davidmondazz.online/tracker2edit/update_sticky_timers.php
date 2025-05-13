<?php
// Include database connection
require_once 'includes/db_connect.php';

// Get database connection
$conn = getDbConnection();

// SQL to add is_sticky column to timers table
$sql = "ALTER TABLE timers ADD COLUMN is_sticky TINYINT(1) DEFAULT 0 AFTER manage_status";

// Execute the query
if ($conn->query($sql) === TRUE) {
    echo "Successfully added is_sticky column to timers table.<br>";
} else {
    echo "Error adding is_sticky column: " . $conn->error . "<br>";
}

// Close connection
$conn->close();

echo "Database update completed.";
?> 