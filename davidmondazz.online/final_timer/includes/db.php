<?php
require_once 'config.php';

/**
 * Get database connection
 * @return mysqli Database connection
 */
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }
    
    $conn->set_charset('utf8mb4');
    return $conn;
} 