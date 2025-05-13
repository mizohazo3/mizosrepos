<?php
// Disable error display in production
// We want clean JSON output without PHP errors
error_reporting(0);
ini_set('display_errors', 0);

// Include the configuration file
require_once __DIR__ . '/../config.php';

// Establish database connection
function getDbConnection() {
    global $db_host, $db_user, $db_pass, $db_name;
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        if ($conn->connect_error) {
            // Return error as JSON
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
            exit;
        }
        
        // Set charset to ensure proper character encoding
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        // Return exception as JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database exception: ' . $e->getMessage()]);
        exit;
    }
}

// Function to handle database errors
function handleDbError($conn, $error_message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $error_message,
        'db_error' => $conn->error
    ]);
    $conn->close();
    exit;
}

// Function to format time display
function formatTime($seconds) {
    // Always display as decimal hours with 2 decimal places and comma separators
    $hours = $seconds / 3600;
    return number_format($hours, 2) . " hrs";
}

// Function to format current elapsed time (HH:MM:SS.ms)
function formatElapsedTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = floor($seconds % 60);
    $milliseconds = floor(($seconds - floor($seconds)) * 100);
    
    return sprintf("%02d:%02d:%02d.%02d", $hours, $minutes, $secs, $milliseconds);
}
?> 