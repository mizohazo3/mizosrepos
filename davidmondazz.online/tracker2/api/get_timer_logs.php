<?php
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection (corrected path to root directory)
require_once dirname(__FILE__) . '/../db_connect.php';

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

// Set CORS headers for other requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if timer ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Timer ID is required']);
    exit;
}

$timer_id = intval($_GET['id']);

try {
    // Fetch timer logs for the specified timer
    $sql = "SELECT id, start_time, stop_time, duration, note, created_at 
            FROM timer_logs 
            WHERE timer_id = ? 
            ORDER BY start_time DESC"; 
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $timer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        // Format times for display
        $row['start_time_formatted'] = date('M d, Y g:i A', strtotime($row['start_time']));
        $row['stop_time_formatted'] = $row['stop_time'] ? date('M d, Y g:i A', strtotime($row['stop_time'])) : null;
        
        // Format duration for display (HH:MM:SS)
        $hours = floor($row['duration'] / 3600);
        $minutes = floor(($row['duration'] % 3600) / 60);
        $seconds = $row['duration'] % 60;
        $row['duration_formatted'] = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        
        $logs[] = $row;
    }
    
    echo json_encode(['success' => true, 'logs' => $logs]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?> 