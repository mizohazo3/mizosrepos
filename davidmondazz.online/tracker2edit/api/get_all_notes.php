<?php
// Headers for JSON response
header('Content-Type: application/json');

// Include database connection
require_once '../includes/db_connect.php';

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

// Set CORS headers for other requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get database connection
    $conn = getDbConnection();
    
    // Fetch notes from timer_logs table
    $sql = "SELECT tl.id, t.name as timer_name, t.id as timer_id, 
                   tl.start_time, tl.stop_time, tl.duration, tl.note 
            FROM timer_logs tl
            JOIN timers t ON tl.timer_id = t.id
            WHERE tl.note IS NOT NULL AND tl.note != ''
            ORDER BY tl.start_time DESC";
    
    $result = $conn->query($sql);
    
    $notes = [];
    while ($row = $result->fetch_assoc()) {
        // Format times for display
        $row['start_time_formatted'] = date('M d, Y g:i A', strtotime($row['start_time']));
        $row['stop_time_formatted'] = $row['stop_time'] ? date('M d, Y g:i A', strtotime($row['stop_time'])) : null;
        
        // Format duration for display (HH:MM:SS)
        $hours = floor($row['duration'] / 3600);
        $minutes = floor(($row['duration'] % 3600) / 60);
        $seconds = $row['duration'] % 60;
        $row['duration_formatted'] = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        
        $notes[] = $row;
    }
    
    echo json_encode(['success' => true, 'notes' => $notes]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}
?> 