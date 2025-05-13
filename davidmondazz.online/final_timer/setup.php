<?php
require_once 'includes/config.php';

// Create a database connection without selecting a database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);

// Create categories table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Categories table created successfully or already exists.<br>";
} else {
    die("Error creating categories table: " . $conn->error);
}

// Create timers table
$sql = "CREATE TABLE IF NOT EXISTS timers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    status ENUM('idle', 'running', 'paused', 'stopped') DEFAULT 'idle',
    start_time DATETIME DEFAULT NULL,
    last_paused_time DATETIME DEFAULT NULL,
    total_paused_duration INT UNSIGNED DEFAULT 0,
    total_elapsed_time BIGINT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Timers table created successfully or already exists.<br>";
} else {
    die("Error creating timers table: " . $conn->error);
}

// Add some default categories if none exist
$sql = "SELECT COUNT(*) as count FROM categories";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    $categories = ['Work', 'Personal', 'Study', 'Fitness', 'Other'];
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    
    foreach ($categories as $category) {
        $stmt->bind_param("s", $category);
        $stmt->execute();
    }
    
    echo "Default categories added successfully.<br>";
    $stmt->close();
}

$conn->close();
echo "Setup completed successfully!"; 