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
error_log("resume_timer.php was called with POST data: " . file_get_contents('php://input'));

// Get database connection
$conn = getDbConnection();

// Get and validate input data
$data = json_decode(file_get_contents('php://input'), true);
$timer_id = isset($data['timer_id']) ? (int)$data['timer_id'] : 0;

if ($timer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid timer ID is required']);
    exit;
}

// Check if the timer exists and get its current status
$stmt = $conn->prepare("SELECT id, status FROM timers WHERE id = ?");
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

// Check if the timer is paused (can only resume a paused timer)
if ($timer['status'] !== 'paused') {
    echo json_encode(['success' => false, 'message' => 'Timer is not paused']);
    $conn->close();
    exit;
}

// Get current timestamp
$current_time = date('Y-m-d H:i:s');

// Update the timer status to 'running' and set the start time
$stmt = $conn->prepare("
    UPDATE timers 
    SET status = 'running', 
        start_time = ?
    WHERE id = ?
");
$stmt->bind_param("si", $current_time, $timer_id);

if ($stmt->execute()) {
    // Get the updated timer
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
    
    // Format the response
    $response = [
        'success' => true,
        'message' => 'Timer resumed successfully',
        'timer' => [
            'id' => $timer['id'],
            'name' => $timer['name'],
            'category_id' => $timer['category_id'],
            'category_name' => $timer['category_name'],
            'status' => $timer['status'],
            'total_time' => (int)$timer['total_time'],
            'total_time_formatted' => formatTime((int)$timer['total_time']),
            'current_elapsed' => (int)$timer['pause_time'],
            'current_elapsed_formatted' => formatElapsedTime((int)$timer['pause_time']),
            'start_time' => $timer['start_time'],
            'updated_at' => $timer['updated_at'],
            'rank_name' => $timer['rank_name'],
            'time_format' => $timer['time_format'],
            'running_count' => (int)$timer['running_count']
        ]
    ];
    
    echo json_encode($response);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to resume timer: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?> 