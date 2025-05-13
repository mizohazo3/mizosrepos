<?php
function getDB() {
    // Database connection parameters - Updated for online server
    $dbHost = 'localhost'; // Hostname for online server
    $dbName = 'mcgkxyz_tracker'; // Database name for online server
    $dbUser = 'mcgkxyz_masterpop'; // Username for online server
    $dbPass = 'aA0109587045'; // Password for online server

    try {
        // Create PDO instance
        $db = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);

        // Set the PDO error mode to exception
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $db;
    } catch(PDOException $e) {
        // Log the error or handle it more gracefully
        error_log("Database Connection failed: " . $e->getMessage());
        // Return null to indicate failure
        return null;
    }
}

// Establish the connection and assign to $con
$con = getDB();

// Check if connection was successful before proceeding in including scripts
if ($con === null) {
    // Display a user-friendly error message and stop script execution
    // Append the specific PDO error for better debugging during development
    // In production, you might want a more generic message.
    die("Database connection failed. Please check the configuration or contact the administrator.");
}
?>