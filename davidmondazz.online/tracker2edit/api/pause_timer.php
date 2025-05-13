<?php
// Include database connection
require_once '../includes/db_connect.php';

// Headers for JSON response
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST method is allowed']);
    exit;
}

// Get database connection
$conn = getDbConnection();

// Get and validate input data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !is_numeric($data['id'])) {
    echo json_encode(['error' => 'Valid timer ID is required']);
    exit;
}

// Sanitize input
$timer_id = (int)$data['id'];

// Check if the timer exists and get its current status and start time
$stmt = $conn->prepare("SELECT id, status, start_time, pause_time FROM timers WHERE id = ?");
$stmt->bind_param("i", $timer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Timer not found']);
    $stmt->close();
    $conn->close();
    exit;
}

$timer = $result->fetch_assoc();
$stmt->close();

// Check if the timer is running
if ($timer['status'] !== 'running') {
    echo json_encode(['error' => 'Timer is not running']);
    $conn->close();
    exit;
}

// Calculate elapsed time since start
$start_time = strtotime($timer['start_time']);
$current_time = microtime(true); // Use microtime for more precision
$elapsed_seconds = $current_time - $start_time;

// Add any previous pause time
$total_pause_time = (float)$timer['pause_time'] + $elapsed_seconds;

// Update the timer status to 'paused' and update the pause_time
$stmt = $conn->prepare("
    UPDATE timers 
    SET status = 'paused', 
        pause_time = ?
    WHERE id = ?
");
$stmt->bind_param("ii", $total_pause_time, $timer_id);

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
        'message' => 'Timer paused successfully',
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
            'updated_at' => $timer['updated_at']
        ]
    ];
    
    echo json_encode($response);
} else {
    handleDbError($conn, 'Failed to pause timer');
}

$stmt->close();
$conn->close();
?> 