<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the configuration file
require_once 'config.php';

// Function to display messages
function display_message($message, $is_error = false) {
    echo ($is_error ? "<div class='alert alert-danger'>" : "<div class='alert alert-success'>") . $message . "</div>";
}

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    display_message("Connected to MySQL server successfully");
    
    // Add manage_status column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM timers LIKE 'manage_status'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE timers ADD COLUMN manage_status VARCHAR(50) NULL DEFAULT NULL AFTER total_time";
        if ($conn->query($sql) === TRUE) {
            display_message("Column 'manage_status' added to timers table successfully");
        } else {
            throw new Exception("Error adding column 'manage_status': " . $conn->error);
        }
    } else {
        display_message("Column 'manage_status' already exists");
    }
    
    // Add links column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM timers LIKE 'links'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE timers ADD COLUMN links TEXT NULL DEFAULT NULL AFTER manage_status";
        if ($conn->query($sql) === TRUE) {
            display_message("Column 'links' added to timers table successfully");
        } else {
            throw new Exception("Error adding column 'links': " . $conn->error);
        }
    } else {
        display_message("Column 'links' already exists");
    }
    
    display_message("Database update completed successfully!");
    $conn->close();
    
} catch (Exception $e) {
    display_message($e->getMessage(), true);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer Tracking System - Update Columns</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 40px;
            font-family: 'Roboto', sans-serif;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0d6efd;
            margin-bottom: 30px;
        }
        .btn-home {
            margin-top: 20px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>Timer Tracking System - Update Columns</h1>
        <div class="alert alert-info">
            Database structure update completed. See the messages above for details.
        </div>
        <a href="index.php" class="btn btn-primary btn-home">Go to Timer Application</a>
        <a href="import_data.php" class="btn btn-success btn-home">Import Data</a>
    </div>
</body>
</html> 