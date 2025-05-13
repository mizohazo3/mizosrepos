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
    $conn = new mysqli($db_host, $db_user, $db_pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    display_message("Connected to MySQL server successfully");
    
    // Select the database
    $conn->select_db($db_name);
    display_message("Database '$db_name' selected");
    
    // Drop existing tables
    $conn->query("DROP TABLE IF EXISTS timers");
    display_message("Dropped timers table if it existed");
    
    $conn->query("DROP TABLE IF EXISTS categories");
    display_message("Dropped categories table if it existed");
    
    // Create categories table
    $sql = "CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        display_message("Table 'categories' created");
    } else {
        throw new Exception("Error creating categories table: " . $conn->error);
    }
    
    // Create timers table
    $sql = "CREATE TABLE IF NOT EXISTS `timers` (
        `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `category_id` INT(11) UNSIGNED NOT NULL,
        `status` ENUM('idle', 'running', 'paused') NOT NULL DEFAULT 'idle',
        `start_time` TIMESTAMP NULL DEFAULT NULL,
        `pause_time` BIGINT UNSIGNED DEFAULT 0 COMMENT 'Accumulated pause time in seconds for current interval',
        `total_time` BIGINT UNSIGNED DEFAULT 0 COMMENT 'Total accumulated time in seconds (updated on stop)',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        display_message("Table 'timers' created");
    } else {
        throw new Exception("Error creating timers table: " . $conn->error);
    }
    
    // Insert default categories
    $default_categories = ["Work", "Personal", "Study", "Health", "Entertainment"];
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    
    foreach ($default_categories as $category) {
        $stmt->bind_param("s", $category);
        $stmt->execute();
    }
    
    display_message("Default categories created");
    $stmt->close();
    
    display_message("Database reset completed successfully!");
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
        <h1>Timer Tracking System - Reset Tables</h1>
        <div class="alert alert-info">
            Tables have been reset. See the messages above for details.
        </div>
        <a href="index.php" class="btn btn-primary btn-home">Go to Timer Application</a>
        <a href="debug.html" class="btn btn-secondary btn-home">Go to Debug Page</a>
    </div>
</body>
</html> 