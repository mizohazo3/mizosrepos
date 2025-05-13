<?php
// Enable error reporting for setup script
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the configuration file
require_once 'config.php';

// Function to display setup messages
function display_message($message, $is_error = false) {
    echo ($is_error ? "ERROR: " : "SUCCESS: ") . $message . "<br>";
}

// Connect to MySQL server
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    display_message("Connected to MySQL server successfully");
    
    // Check if the note column already exists in timer_logs table
    $result = $conn->query("SHOW COLUMNS FROM timer_logs LIKE 'note'");
    if ($result->num_rows > 0) {
        display_message("Column 'note' already exists in timer_logs table.");
    } else {
        // Add the note column to timer_logs table
        $sql = "ALTER TABLE timer_logs ADD COLUMN note TEXT NULL AFTER duration";
        
        if ($conn->query($sql) === TRUE) {
            display_message("Column 'note' added to timer_logs table successfully");
        } else {
            throw new Exception("Error adding column 'note' to timer_logs table: " . $conn->error);
        }
    }
    
    // Check if manage_status column exists in timers table
    $result = $conn->query("SHOW COLUMNS FROM timers LIKE 'manage_status'");
    if ($result->num_rows > 0) {
        display_message("Column 'manage_status' already exists in timers table.");
    } else {
        // Add the manage_status column to timers table
        $sql = "ALTER TABLE timers ADD COLUMN manage_status VARCHAR(50) NULL DEFAULT NULL AFTER total_time";
        
        if ($conn->query($sql) === TRUE) {
            display_message("Column 'manage_status' added to timers table successfully");
        } else {
            throw new Exception("Error adding column 'manage_status' to timers table: " . $conn->error);
        }
    }
    
    // Check if links column exists in timers table
    $result = $conn->query("SHOW COLUMNS FROM timers LIKE 'links'");
    if ($result->num_rows > 0) {
        display_message("Column 'links' already exists in timers table.");
    } else {
        // Add the links column to timers table
        $sql = "ALTER TABLE timers ADD COLUMN links TEXT NULL DEFAULT NULL AFTER manage_status";
        
        if ($conn->query($sql) === TRUE) {
            display_message("Column 'links' added to timers table successfully");
        } else {
            throw new Exception("Error adding column 'links' to timers table: " . $conn->error);
        }
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
    <title>Timer Tracking System - Database Update</title>
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
        <h1>Timer Tracking System - Database Update</h1>
        <div class="alert alert-info">
            Database structure update completed. See the messages above for details.
        </div>
        <a href="index.php" class="btn btn-primary btn-home">Go to Timer Application</a>
        <a href="timer_details.php?id=1" class="btn btn-secondary btn-home">View Timer Details</a>
    </div>
</body>
</html> 