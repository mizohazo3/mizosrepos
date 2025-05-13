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

// Connect to database
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    display_message("Connected to MySQL server successfully");
    
    // First delete timer_logs data since it has foreign key constraints
    $sql = "DELETE FROM timer_logs";
    if ($conn->query($sql) === TRUE) {
        display_message("Deleted " . $conn->affected_rows . " records from timer_logs table");
    } else {
        throw new Exception("Error truncating timer_logs table: " . $conn->error);
    }
    
    // Then delete timers data
    $sql = "DELETE FROM timers";
    if ($conn->query($sql) === TRUE) {
        display_message("Deleted " . $conn->affected_rows . " records from timers table");
    } else {
        throw new Exception("Error truncating timers table: " . $conn->error);
    }
    
    // Reset auto-increment counters (optional)
    $sql = "ALTER TABLE timers AUTO_INCREMENT = 1";
    if ($conn->query($sql) === TRUE) {
        display_message("Reset timers AUTO_INCREMENT to 1");
    } else {
        throw new Exception("Error resetting AUTO_INCREMENT: " . $conn->error);
    }
    
    $sql = "ALTER TABLE timer_logs AUTO_INCREMENT = 1";
    if ($conn->query($sql) === TRUE) {
        display_message("Reset timer_logs AUTO_INCREMENT to 1");
    } else {
        throw new Exception("Error resetting AUTO_INCREMENT: " . $conn->error);
    }
    
    display_message("Tables successfully emptied!");
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
    <title>Timer Tracking System - Reset Tables</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 40px;
            font-family: 'Roboto', sans-serif;
        }
        .reset-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #dc3545;
            margin-bottom: 30px;
        }
        .btn-home {
            margin-top: 20px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h1><i class="fas fa-trash-alt me-3"></i>Timer Tracking System - Reset Tables</h1>
        <div class="alert alert-info">
            The timer-related tables have been emptied. Your categories remain intact.
        </div>
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary btn-home">
                <i class="fas fa-home me-2"></i>Go to Timer Application
            </a>
            <a href="import_data.php" class="btn btn-success btn-home">
                <i class="fas fa-file-import me-2"></i>Import Data
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 