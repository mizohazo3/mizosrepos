<?php
// Include database connection
require_once '../includes/db_connect.php';

// Headers for JSON response
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

// Get database connection
$conn = getDbConnection();

// Start transaction
$conn->begin_transaction();

try {
    // Find all running and paused timers
    $stmt = $conn->prepare("SELECT id, status, start_time, pause_time FROM timers WHERE status IN ('running', 'paused')");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stopped_count = 0;
    $current_time = date('Y-m-d H:i:s');
    
    while ($timer = $result->fetch_assoc()) {
        $timer_id = $timer['id'];
        
        // Calculate the duration based on status
        $duration = 0;
        if ($timer['status'] === 'running') {
            // For running timers: Calculate elapsed time since start + any previous pause_time
            $start_time_ts = strtotime($timer['start_time']);
            $current_time_ts = time();
            $elapsed_since_start = $current_time_ts - $start_time_ts;
            $duration = $timer['pause_time'] + $elapsed_since_start;
        } else if ($timer['status'] === 'paused') {
            // For paused timers: Just use the accumulated pause_time
            $duration = $timer['pause_time'];
        }
        
        // Update the timer status to 'idle', reset start_time and pause_time, and add to total_time
        $update_stmt = $conn->prepare("
            UPDATE timers 
            SET status = 'idle', 
                start_time = NULL, 
                pause_time = 0, 
                total_time = total_time + ?
            WHERE id = ?
        ");
        $update_stmt->bind_param("ii", $duration, $timer_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Update timer_logs to add stop_time and duration for the latest log entry
        $log_stmt = $conn->prepare("
            UPDATE timer_logs 
            SET stop_time = ?, 
                duration = ? 
            WHERE timer_id = ? 
            AND stop_time IS NULL 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $log_stmt->bind_param("sii", $current_time, $duration, $timer_id);
        $log_stmt->execute();
        $log_stmt->close();
        
        $stopped_count++;
    }
    
    $stmt->close();
    
    // Commit the transaction
    $conn->commit();
    
    // Return success response with count of stopped timers
    echo json_encode([
        'success' => true, 
        'message' => $stopped_count . ' timers stopped successfully',
        'stopped_count' => $stopped_count
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    
    // Log the error
    error_log("Error stopping all timers: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Failed to stop all timers: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?> 