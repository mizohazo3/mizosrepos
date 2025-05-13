<?php
require_once '../config.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Default response
$response = [
    'success' => false,
    'total_seconds' => 0,
    'message' => 'Failed to retrieve total time'
];

try {
    // Connect to the database
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Query to get the sum of total_time from all timers
    $sql = "SELECT SUM(total_time) as total_seconds FROM timers";
    $result = $conn->query($sql);
    
    if ($result) {
        $row = $result->fetch_assoc();
        $total_seconds = $row['total_seconds'] ?? 0;
        
        // Update the response
        $response = [
            'success' => true,
            'total_seconds' => (int)$total_seconds,
            'message' => 'Total time retrieved successfully'
        ];
    }
    
    // Close the connection
    $conn->close();
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return the response as JSON
echo json_encode($response); 