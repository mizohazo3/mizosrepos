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

// Connect to MySQL server (without selecting a database)
try {
    $conn = new mysqli($db_host, $db_user, $db_pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    display_message("Connected to MySQL server successfully");
    
    // Check if database exists
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");
    $dbExists = $result && $result->num_rows > 0;
    
    if ($dbExists) {
        display_message("Database '$db_name' already exists");
    } else {
        // Create database if it doesn't exist
        $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if ($conn->query($sql) === TRUE) {
            display_message("Database '$db_name' created successfully");
        } else {
            throw new Exception("Error creating database: " . $conn->error);
        }
    }
    
    // Select the database
    $conn->select_db($db_name);
    display_message("Database '$db_name' selected");
    
    // Create categories table
    $sql = "CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        display_message("Table 'categories' created or already exists");
    } else {
        throw new Exception("Error creating categories table: " . $conn->error);
    }
    
    // Create timers table with leveling system columns
    $sql = "CREATE TABLE IF NOT EXISTS `timers` (
        `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `category_id` INT(11) UNSIGNED NOT NULL,
        `status` ENUM('idle', 'running', 'paused') NOT NULL DEFAULT 'idle',
        `start_time` TIMESTAMP NULL DEFAULT NULL,
        `pause_time` BIGINT UNSIGNED DEFAULT 0 COMMENT 'Accumulated pause time in seconds for current interval',
        `total_time` BIGINT UNSIGNED DEFAULT 0 COMMENT 'Total accumulated time in seconds (updated on stop)',
        `manage_status` VARCHAR(50) NULL DEFAULT NULL,
        `links` TEXT NULL DEFAULT NULL,
        `xp` INT DEFAULT 0 COMMENT 'Experience points for leveling system',
        `level` INT DEFAULT 1 COMMENT 'Current level of the timer',
        `experience` INT DEFAULT 0 COMMENT 'Experience points for leveling system',
        `is_sticky` TINYINT(1) DEFAULT 0 COMMENT 'Whether timer should be pinned at top',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        display_message("Table 'timers' created or already exists");
    } else {
        throw new Exception("Error creating timers table: " . $conn->error);
    }
    
    // Create timer_logs table
    $sql = "CREATE TABLE IF NOT EXISTS `timer_logs` (
        `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `timer_id` INT(11) UNSIGNED NOT NULL,
        `start_time` TIMESTAMP NULL DEFAULT NULL,
        `stop_time` TIMESTAMP NULL DEFAULT NULL,
        `duration` BIGINT UNSIGNED DEFAULT 0 COMMENT 'Session duration in seconds',
        `note` TEXT NULL DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (timer_id) REFERENCES timers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        display_message("Table 'timer_logs' created or already exists");
    } else {
        throw new Exception("Error creating timer_logs table: " . $conn->error);
    }
    
    // Create levels table for the leveling system
    $sql = "CREATE TABLE IF NOT EXISTS `levels` (
        `level` INT PRIMARY KEY,
        `threshold` INT NOT NULL,
        `title` VARCHAR(50) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        display_message("Table 'levels' created or already exists");
    } else {
        throw new Exception("Error creating levels table: " . $conn->error);
    }
    
    // Create xp_logs table for tracking experience points
    $sql = "CREATE TABLE IF NOT EXISTS `xp_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `timer_id` INT NOT NULL,
        `xp_gained` INT NOT NULL,
        `level_before` INT NOT NULL,
        `level_after` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (timer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        display_message("Table 'xp_logs' created or already exists");
    } else {
        throw new Exception("Error creating xp_logs table: " . $conn->error);
    }
    
    // Create levels_ranks table for the timer ranking system
    $sql = "CREATE TABLE IF NOT EXISTS `levels_ranks` (
        `level` INT PRIMARY KEY,
        `rank_name` VARCHAR(50) NOT NULL,
        `time_format` VARCHAR(50) DEFAULT 'hh:mm:ss',
        `min_hours` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        display_message("Table 'levels_ranks' created or already exists");
    } else {
        throw new Exception("Error creating levels_ranks table: " . $conn->error);
    }
    
    // Create timer_experience_logs table for detailed XP tracking
    $sql = "CREATE TABLE IF NOT EXISTS `timer_experience_logs` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `timer_id` INT UNSIGNED NOT NULL,
        `experience_gained` INT NOT NULL DEFAULT 0,
        `level_before` INT NOT NULL DEFAULT 1,
        `level_after` INT NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (timer_id),
        FOREIGN KEY (timer_id) REFERENCES timers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        display_message("Table 'timer_experience_logs' created or already exists");
    } else {
        display_message("Note: Couldn't create timer_experience_logs table: " . $conn->error, true);
    }
    
    // Insert default categories if the categories table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $default_categories = ["Work", "Personal", "Study", "Health", "Entertainment"];
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        
        foreach ($default_categories as $category) {
            $stmt->bind_param("s", $category);
            $stmt->execute();
        }
        
        display_message("Default categories created");
        $stmt->close();
    } else {
        display_message("Categories already exist, skipping default category creation");
    }
    
    // Populate levels table if empty
    $result = $conn->query("SELECT COUNT(*) as count FROM levels");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        display_message("Populating levels table");
        
        // Define base XP and growth factor
        $base_xp = 100;
        $growth_factor = 1.3;
        
        // Calculate thresholds for 50 levels
        $level_data = [];
        $level_titles = [
            "Beginner", "Novice", "Apprentice", "Adept", "Skilled",
            "Proficient", "Expert", "Master", "Grandmaster", "Legend",
            "Mythic", "Transcendent", "Cosmic", "Divine", "Eternal",
            "Alpha", "Beta", "Gamma", "Delta", "Epsilon",
            "Zeta", "Eta", "Theta", "Iota", "Kappa",
            "Lambda", "Mu", "Nu", "Xi", "Omicron",
            "Pi", "Rho", "Sigma", "Tau", "Upsilon",
            "Phi", "Chi", "Psi", "Omega", "Aleph",
            "Infinity", "Oracle", "Nexus", "Apex", "Zenith",
            "Paragon", "Sovereign", "Ascendant", "Immortal", "Transcendant"
        ];
        
        // Insert levels with increasing thresholds
        for ($i = 1; $i <= 50; $i++) {
            // For level 1, threshold is 0
            if ($i == 1) {
                $threshold = 0;
            } 
            // For levels 2-40, use exponential formula
            else if ($i <= 40) {
                $threshold = round($base_xp * pow($growth_factor, $i - 2));
                // Cap threshold at INT max value to avoid overflow
                $threshold = min($threshold, 2147483647);
            } 
            // For levels 41-50, use a fixed increment to avoid INT overflow
            else {
                $prev_level = $i - 1;
                $prev_threshold = round($base_xp * pow($growth_factor, $prev_level - 2));
                $prev_threshold = min($prev_threshold, 2147483647);
                $increment = 1000000; // Fixed increment for higher levels
                $threshold = min($prev_threshold + $increment, 2147483647);
            }
            
            $title = isset($level_titles[$i-1]) ? $level_titles[$i-1] : "Level " . $i;
            $level_data[] = "($i, $threshold, '$title')";
        }
        
        // Batch insert all levels
        $sql = "INSERT INTO levels (level, threshold, title) VALUES " . implode(", ", $level_data);
        
        if ($conn->query($sql)) {
            display_message("Populated levels table with 50 levels");
        } else {
            display_message("Error populating levels table: " . $conn->error, true);
        }
    } else {
        display_message("Levels table already has data");
    }
    
    // Populate levels_ranks table if empty
    $result = $conn->query("SELECT COUNT(*) as count FROM levels_ranks");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        display_message("Populating levels_ranks table");
        
        // Define ranks with their display formats
        $ranks = [
            [1, "Novice", "hh:mm:ss", 0],
            [10, "Beginner", "hh:mm:ss", 5],
            [20, "Intermediate", "hh:mm:ss", 20],
            [30, "Advanced", "hh:mm:ss.ms", 50],
            [40, "Expert", "hh:mm:ss.ms", 100],
            [50, "Master", "HH:MM:SS.ms", 200],
            [60, "Grandmaster", "HH:MM:SS.ms", 500],
            [70, "Champion", "HH:MM:SS.MS", 1000],
            [80, "Elite", "HH:MM:SS.MS", 2000],
            [90, "Legendary", "HH:MM:SS.MS", 5000],
            [100, "Ultimate", "HH:MM:SS.MS", 10000]
        ];
        
        $stmt = $conn->prepare("INSERT INTO levels_ranks (level, rank_name, time_format, min_hours) VALUES (?, ?, ?, ?)");
        
        foreach ($ranks as $rank) {
            $stmt->bind_param("issi", $rank[0], $rank[1], $rank[2], $rank[3]);
            if ($stmt->execute()) {
                display_message("Added rank: " . $rank[1]);
            } else {
                display_message("Error adding rank " . $rank[1] . ": " . $stmt->error, true);
            }
        }
        
        $stmt->close();
    } else {
        display_message("Levels ranks table already has data");
    }
    
    display_message("Database setup completed successfully!");
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
    <title>Timer Tracking System - Setup</title>
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
        <h1>Timer Tracking System - Database Setup</h1>
        <div class="alert alert-info">
            Setup process completed. See the messages above for details.
        </div>
        <a href="index.php" class="btn btn-primary btn-home">Go to Timer Application</a>
        <a href="debug.html" class="btn btn-secondary btn-home">Go to Debug Page</a>
    </div>
</body>
</html> 