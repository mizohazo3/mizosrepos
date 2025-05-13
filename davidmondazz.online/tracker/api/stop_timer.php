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

// For debugging
error_log("stop_timer.php was called with POST data: " . print_r($_POST, true));

// Get database connection
$conn = getDbConnection();

// Get and validate input data
$timer_id = isset($_POST['timer_id']) ? (int)$_POST['timer_id'] : 0;

if ($timer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid timer ID is required']);
    exit;
}

// Check if the timer exists and get its current status
$stmt = $conn->prepare("SELECT id, status, start_time, pause_time, is_sticky FROM timers WHERE id = ?");
$stmt->bind_param("i", $timer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Timer not found']);
    $stmt->close();
    $conn->close();
    exit;
}

$timer = $result->fetch_assoc();
$stmt->close();

// Check if the timer is running or paused (can only stop a running or paused timer)
if ($timer['status'] === 'idle') {
    echo json_encode(['success' => false, 'message' => 'Timer is already stopped']);
    $conn->close();
    exit;
}

// Get current timestamp
$current_time = date('Y-m-d H:i:s');

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

// Start transaction
$conn->begin_transaction();

try {
    // Update the timer status to 'idle', reset start_time and pause_time, and add to total_time
    $stmt = $conn->prepare("
        UPDATE timers 
        SET status = 'idle', 
            start_time = NULL, 
            pause_time = 0, 
            total_time = total_time + ?
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $duration, $timer_id);
    $stmt->execute();
    $stmt->close();
    
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
    
    // Commit the transaction
    $conn->commit();
    
    // Get the updated timer with all necessary fields
    $stmt = $conn->prepare("
        SELECT t.*, c.name as category_name, 
        lr.rank_name, lr.time_format, 
        (SELECT COUNT(*) FROM timers WHERE status = 'running') as running_count
        FROM timers t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN levels_ranks lr ON t.level = lr.level
        WHERE t.id = ?
    ");
    $stmt->bind_param("i", $timer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $timer = $result->fetch_assoc();
    $stmt->close();
    
    // Format the response
    $response = [
        'success' => true,
        'message' => 'Timer stopped successfully',
        'timer' => [
            'id' => $timer['id'],
            'name' => $timer['name'],
            'category_id' => $timer['category_id'],
            'category_name' => $timer['category_name'],
            'status' => $timer['status'],
            'manage_status' => $timer['manage_status'],
            'is_sticky' => (bool)$timer['is_sticky'], // Preserve sticky status
            'total_time' => (int)$timer['total_time'],
            'total_time_formatted' => formatTime((int)$timer['total_time']),
            'updated_at' => $timer['updated_at'],
            'experience' => (int)$timer['experience'],
            'level' => (int)$timer['level'],
            'rank_name' => $timer['rank_name'],
            'time_format' => $timer['time_format']
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to stop timer: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 