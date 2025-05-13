<?php
/**
 * Automated Installation Script for Timer Tracking System
 * This script will:
 * 1. Set up the database and tables
 * 2. Install required PHP dependencies
 * 3. Create a basic configuration file
 * 4. Configure the WebSocket server
 */

// Set error reporting for better debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration variables
$dbHost = isset($_POST['db_host']) ? $_POST['db_host'] : 'localhost';
$dbUser = isset($_POST['db_user']) ? $_POST['db_user'] : 'root';
$dbPass = isset($_POST['db_pass']) ? $_POST['db_pass'] : '';
$dbName = isset($_POST['db_name']) ? $_POST['db_name'] : 'timer_tracker';
$webSocketPort = isset($_POST['ws_port']) ? $_POST['ws_port'] : 8080;
$serverIP = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? '127.0.0.1';

// Function to check if we're processing the form
function isFormSubmitted() {
    return isset($_POST['install']) && $_POST['install'] === 'true';
}

// Function to check if composer is installed
function isComposerInstalled() {
    $output = [];
    $returnCode = 0;
    exec('composer --version 2>&1', $output, $returnCode);
    return $returnCode === 0;
}

// Function to check PHP version
function checkPhpVersion() {
    return version_compare(PHP_VERSION, '7.4.0', '>=');
}

// Function to check database connection
function checkDbConnection($host, $user, $pass) {
    try {
        $conn = new mysqli($host, $user, $pass);
        if ($conn->connect_error) {
            return false;
        }
        $conn->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Function to create database and tables
function setupDatabase($host, $user, $pass, $name) {
    $messages = [];
    $success = true;
    
    try {
        // Connect to MySQL
        $conn = new mysqli($host, $user, $pass);
        
        if ($conn->connect_error) {
            $messages[] = "Database connection failed: " . $conn->connect_error;
            return [false, $messages];
        }
        
        // Create database if it doesn't exist
        $sql = "CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
        if ($conn->query($sql) !== TRUE) {
            $messages[] = "Error creating database: " . $conn->error;
            $success = false;
        } else {
            $messages[] = "Database created successfully or already exists.";
        }
        
        // Select the database
        $conn->select_db($name);
        
        // Create categories table
        $sql = "CREATE TABLE IF NOT EXISTS `categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if ($conn->query($sql) !== TRUE) {
            $messages[] = "Error creating categories table: " . $conn->error;
            $success = false;
        } else {
            $messages[] = "Categories table created successfully.";
        }
        
        // Create timers table
        $sql = "CREATE TABLE IF NOT EXISTS `timers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `category_id` int(11) NOT NULL,
            `status` enum('idle','running','paused','stopped') NOT NULL DEFAULT 'idle',
            `start_time` datetime DEFAULT NULL,
            `last_paused_time` datetime DEFAULT NULL,
            `total_elapsed_time` int(11) NOT NULL DEFAULT 0,
            `total_paused_duration` int(11) NOT NULL DEFAULT 0,
            `current_elapsed` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `category_id` (`category_id`),
            CONSTRAINT `timers_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if ($conn->query($sql) !== TRUE) {
            $messages[] = "Error creating timers table: " . $conn->error;
            $success = false;
        } else {
            $messages[] = "Timers table created successfully.";
        }
        
        // Create user_preferences table
        $sql = "CREATE TABLE IF NOT EXISTS `user_preferences` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `preference_key` varchar(50) NOT NULL,
            `preference_value` text NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `preference_key` (`preference_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if ($conn->query($sql) !== TRUE) {
            $messages[] = "Error creating user_preferences table: " . $conn->error;
            $success = false;
        } else {
            $messages[] = "User preferences table created successfully.";
        }
        
        // Insert default categories
        $defaultCategories = ['Work', 'Personal', 'Study', 'Exercise', 'Project'];
        foreach ($defaultCategories as $category) {
            $stmt = $conn->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
            if ($stmt === false) {
                $messages[] = "Error preparing statement: " . $conn->error;
                $success = false;
                continue;
            }
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $stmt->close();
        }
        $messages[] = "Default categories added.";
        
        $conn->close();
    } catch (Exception $e) {
        $messages[] = "Exception: " . $e->getMessage();
        $success = false;
    }
    
    return [$success, $messages];
}

// Function to create config file
function createConfigFile($host, $user, $pass, $name) {
    $configContent = "<?php
// Database connection configuration
define('DB_HOST', '$host');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_NAME', '$name');

// Set default timezone
date_default_timezone_set('UTC');
";

    file_put_contents('includes/config.php', $configContent);
    return file_exists('includes/config.php');
}

// Function to install Composer dependencies
function installComposerDependencies() {
    $output = [];
    $returnCode = 0;
    exec('composer require cboden/ratchet 2>&1', $output, $returnCode);
    return [$returnCode === 0, $output];
}

// Function to update the React Native app configuration
function updateReactNativeConfig($serverIP, $port) {
    $appJsPath = 'android_app/App.js';
    
    if (!file_exists($appJsPath)) {
        return false;
    }
    
    $appJsContent = file_get_contents($appJsPath);
    $appJsContent = preg_replace(
        '/wsUrl: \'ws:\/\/[^:]+:(\d+)\'/',
        "wsUrl: 'ws://$serverIP:$port'",
        $appJsContent
    );
    
    file_put_contents($appJsPath, $appJsContent);
    return true;
}

// Process the installation if form is submitted
$installationResult = '';
$installationMessages = [];

if (isFormSubmitted()) {
    $installationSuccess = true;
    
    // Check PHP version
    if (!checkPhpVersion()) {
        $installationMessages[] = "❌ PHP version check failed. Required: PHP 7.4 or higher.";
        $installationSuccess = false;
    } else {
        $installationMessages[] = "✅ PHP version check passed.";
    }
    
    // Check Composer
    if (!isComposerInstalled()) {
        $installationMessages[] = "❌ Composer is not installed. Please install Composer first.";
        $installationSuccess = false;
    } else {
        $installationMessages[] = "✅ Composer is installed.";
    }
    
    // Check database connection
    if (!checkDbConnection($dbHost, $dbUser, $dbPass)) {
        $installationMessages[] = "❌ Could not connect to database server.";
        $installationSuccess = false;
    } else {
        $installationMessages[] = "✅ Database connection successful.";
        
        // Set up database and tables
        list($dbSetupSuccess, $dbMessages) = setupDatabase($dbHost, $dbUser, $dbPass, $dbName);
        $installationMessages = array_merge($installationMessages, $dbMessages);
        
        if (!$dbSetupSuccess) {
            $installationSuccess = false;
        }
    }
    
    // Create config file
    if ($installationSuccess) {
        if (createConfigFile($dbHost, $dbUser, $dbPass, $dbName)) {
            $installationMessages[] = "✅ Configuration file created successfully.";
        } else {
            $installationMessages[] = "❌ Failed to create configuration file.";
            $installationSuccess = false;
        }
    }
    
    // Install Composer dependencies
    if ($installationSuccess && isComposerInstalled()) {
        list($composerSuccess, $composerOutput) = installComposerDependencies();
        if ($composerSuccess) {
            $installationMessages[] = "✅ Composer dependencies installed successfully.";
        } else {
            $installationMessages[] = "❌ Failed to install Composer dependencies.";
            $installationMessages[] = implode("\n", $composerOutput);
            $installationSuccess = false;
        }
    }
    
    // Update React Native configuration
    if ($installationSuccess) {
        if (updateReactNativeConfig($serverIP, $webSocketPort)) {
            $installationMessages[] = "✅ React Native configuration updated successfully.";
        } else {
            $installationMessages[] = "⚠️ Could not update React Native configuration automatically.";
        }
    }
    
    // Final result
    if ($installationSuccess) {
        $installationResult = "success";
        $installationMessages[] = "🎉 Installation completed successfully!";
    } else {
        $installationResult = "error";
        $installationMessages[] = "❌ Installation failed. Please check the errors above.";
    }
}

// HTML layout
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer Tracking System Installation</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
        }
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #3498db;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
        }
        h2 {
            color: #2c3e50;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
        .results {
            margin-top: 30px;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .terminal {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        .next-steps {
            margin-top: 30px;
            padding: 15px;
            background-color: #e9f7fe;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        code {
            background: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Timer Tracking System Installation</h1>
        
        <?php if ($installationResult === 'success'): ?>
            <div class="results success">
                <h2>Installation Complete!</h2>
                <div class="terminal">
                    <?php foreach ($installationMessages as $message): ?>
                        <div><?php echo htmlspecialchars($message); ?></div>
                    <?php endforeach; ?>
                </div>
                
                <div class="next-steps">
                    <h3>Next Steps:</h3>
                    <ol>
                        <li>
                            <strong>Start the WebSocket server:</strong>
                            <code>php ws_server.php</code>
                        </li>
                        <li>
                            <strong>Access your web application:</strong>
                            <a href="index.php">Open Timer Tracker Web App</a>
                        </li>
                        <li>
                            <strong>For the mobile app:</strong>
                            <ul>
                                <li>Install Node.js and npm</li>
                                <li>Install Expo CLI: <code>npm install -g expo-cli</code></li>
                                <li>Navigate to TimerTrackerApp directory: <code>cd TimerTrackerApp</code></li>
                                <li>Install dependencies: <code>npm install</code></li>
                                <li>Start the app: <code>npm start</code></li>
                            </ul>
                        </li>
                    </ol>
                </div>
            </div>
        <?php elseif ($installationResult === 'error'): ?>
            <div class="results error">
                <h2>Installation Failed</h2>
                <div class="terminal">
                    <?php foreach ($installationMessages as $message): ?>
                        <div><?php echo htmlspecialchars($message); ?></div>
                    <?php endforeach; ?>
                </div>
                <p>Please fix the errors above and try again.</p>
                <a href="install.php" class="btn">Restart Installation</a>
            </div>
        <?php else: ?>
            <p>This installer will help you set up the Timer Tracking System including the database, required dependencies, and configuration files.</p>
            
            <h2>Prerequisites</h2>
            <ul>
                <li>PHP 7.4 or higher</li>
                <li>MySQL or MariaDB database</li>
                <li>Composer (PHP package manager)</li>
                <li>Web server (Apache, Nginx, etc.)</li>
            </ul>
            
            <form method="post" action="install.php">
                <input type="hidden" name="install" value="true">
                
                <h2>Database Configuration</h2>
                <div class="form-group">
                    <label for="db_host">Database Host:</label>
                    <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($dbHost); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Database Username:</label>
                    <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($dbUser); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Database Password:</label>
                    <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($dbPass); ?>">
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name:</label>
                    <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($dbName); ?>" required>
                </div>
                
                <h2>WebSocket Configuration</h2>
                <div class="form-group">
                    <label for="ws_port">WebSocket Port:</label>
                    <input type="number" id="ws_port" name="ws_port" value="<?php echo htmlspecialchars($webSocketPort); ?>" required>
                </div>
                
                <button type="submit" class="btn">Install Now</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 