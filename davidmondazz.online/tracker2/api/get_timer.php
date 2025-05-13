<?php
// Include database connection
require_once '../includes/db_connect.php';

// Headers for JSON response
header('Content-Type: application/json');

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only GET method is allowed']);
    exit;
}

// Get database connection
$conn = getDbConnection();

// Get and validate input data
$timer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($timer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid timer ID is required']);
    exit;
}

// Get the timer with all necessary fields
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

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Timer not found']);
    $stmt->close();
    $conn->close();
    exit;
}

$timer = $result->fetch_assoc();

// Calculate current elapsed time for running timers
$current_elapsed = 0;
if ($timer['status'] === 'running') {
    $start_time = strtotime($timer['start_time']);
    $current_time = time();
    $current_elapsed = $current_time - $start_time + $timer['pause_time'];
}

// Format the response
$response = [
    'success' => true,
    'timer' => [
        'id' => $timer['id'],
        'name' => $timer['name'],
        'category_id' => $timer['category_id'],
        'category_name' => $timer['category_name'],
        'status' => $timer['status'],
        'manage_status' => $timer['manage_status'],
        'is_sticky' => (bool)$timer['is_sticky'],
        'total_time' => (int)$timer['total_time'],
        'total_time_formatted' => formatTime((int)$timer['total_time']),
        'current_elapsed' => $current_elapsed,
        'current_elapsed_formatted' => formatTime($current_elapsed),
        'updated_at' => $timer['updated_at'],
        'experience' => (int)$timer['experience'],
        'level' => (int)$timer['level'],
        'rank_name' => $timer['rank_name'],
        'time_format' => $timer['time_format']
    ]
];

echo json_encode($response);

$stmt->close();
$conn->close();
?> 