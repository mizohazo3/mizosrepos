<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

// Check if the last_checked field exists
try {
    // Try to select records using the last_checked field
    $stmt = $con->prepare("SELECT last_checked FROM side_effects LIMIT 1");
    $stmt->execute();
    
    // Check if we need to modify the field type to INT
    $stmt = $con->prepare("SHOW COLUMNS FROM side_effects LIKE 'last_checked'");
    $stmt->execute();
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (strpos(strtolower($columnInfo['Type']), 'int') === false) {
        // Alter the column type to INT
        $stmt = $con->prepare("ALTER TABLE side_effects MODIFY COLUMN last_checked INT DEFAULT NULL");
        $stmt->execute();
        echo "Modified last_checked field to INT type.";
    } else {
        echo "last_checked field already exists with the correct type.";
    }
} catch (PDOException $e) {
    // If we get an error, assume the field doesn't exist and create it
    try {
        $stmt = $con->prepare("ALTER TABLE side_effects ADD COLUMN last_checked INT DEFAULT NULL");
        $stmt->execute();
        
        echo "Successfully added last_checked field to the side_effects table.";
    } catch (PDOException $e2) {
        echo "Error adding last_checked field: " . $e2->getMessage();
    }
}
?> 