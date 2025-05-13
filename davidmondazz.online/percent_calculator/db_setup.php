<?php
// MySQL Database Setup Script

// Configuration
$db_host = 'localhost';
$db_user = 'mcgkxyz_masterpop';  // Change to your MySQL username
$db_pass = 'aA0109587045';      // Change to your MySQL password
$db_name = 'mcgkxyz_percent_calculator';  // Database name

// Check if form is submitted
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Connect to MySQL server (without database)
        $conn = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Connect to the database
        $conn->exec("USE `$db_name`");
        
        // Create table
        $conn->exec("CREATE TABLE IF NOT EXISTS calculations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            likes FLOAT NOT NULL,
            views FLOAT NOT NULL,
            percentage FLOAT NOT NULL,
            timestamp INT NOT NULL
        )");
        
        $message = "Database setup completed successfully! The '$db_name' database has been created.";
        $success = true;
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Like Rate Calculator - Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2196F3;
        }
        .box {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        code {
            background-color: #e8e8e8;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
        button {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #1976D2;
        }
        .instructions {
            margin-top: 30px;
        }
        .back-link {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Like Rate Calculator - Database Setup</h1>
    
    <div class="box">
        <h2>MySQL Database Configuration</h2>
        
        <p>Current settings:</p>
        <ul>
            <li>Host: <code><?php echo htmlspecialchars($db_host); ?></code></li>
            <li>Username: <code><?php echo htmlspecialchars($db_user); ?></code></li>
            <li>Password: <code>*****</code></li>
            <li>Database: <code><?php echo htmlspecialchars($db_name); ?></code></li>
        </ul>
        
        <p>If you need to change these settings, please edit the configuration in:</p>
        <ul>
            <li><code>db_setup.php</code></li>
            <li><code>index.php</code></li>
            <li><code>save_calculation.php</code></li>
        </ul>
        
        <?php if ($message): ?>
            <div class="<?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <button type="submit">Set Up Database</button>
        </form>
    </div>
    
    <div class="instructions">
        <h2>Setup Instructions</h2>
        <ol>
            <li>Make sure you have MySQL server installed and running</li>
            <li>Update the database configuration in this file if needed</li>
            <li>Click the "Set Up Database" button to create the database and tables</li>
            <li>Once setup is complete, you can use the Like Rate Calculator</li>
        </ol>
    </div>
    
    <div class="back-link">
        <a href="index.php">Back to Calculator</a>
    </div>
</body>
</html> 