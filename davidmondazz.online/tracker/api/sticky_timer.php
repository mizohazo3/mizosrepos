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

// Get and validate input data
$timer_id = isset($_POST['timer_id']) ? (int)$_POST['timer_id'] : 0;
$sticky = isset($_POST['sticky']) ? (int)$_POST['sticky'] : null;

if ($timer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid timer ID is required']);
    exit;
}

if ($sticky === null) {
    echo json_encode(['success' => false, 'message' => 'Sticky status is required']);
    exit;
}

// Check if the timer exists
$stmt = $conn->prepare("SELECT id, is_sticky FROM timers WHERE id = ?");
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

// Update the timer's sticky status
$stmt = $conn->prepare("UPDATE timers SET is_sticky = ? WHERE id = ?");
$stmt->bind_param("ii", $sticky, $timer_id);

if ($stmt->execute()) {
    // Get the updated timer
    $stmt = $conn->prepare("
        SELECT t.*, c.name as category_name 
        FROM timers t 
        INNER JOIN categories c ON t.category_id = c.id 
        WHERE t.id = ?
    ");
    $stmt->bind_param("i", $timer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $timer = $result->fetch_assoc();
    
    // Format the response
    $response = [
        'success' => true,
        'message' => $sticky ? 'Timer stickied successfully' : 'Timer unstickied successfully',
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
            'updated_at' => $timer['updated_at']
        ]
    ];
    
    // Calculate current elapsed time for running and paused timers
    if ($timer['status'] === 'running') {
        $start_time_ts = strtotime($timer['start_time']);
        $current_time_ts = time();
        $elapsed_seconds = (int)$timer['pause_time'] + ($current_time_ts - $start_time_ts);
        $response['timer']['current_elapsed'] = $elapsed_seconds;
        $response['timer']['current_elapsed_formatted'] = formatElapsedTime($elapsed_seconds);
    } else if ($timer['status'] === 'paused') {
        $elapsed_seconds = (int)$timer['pause_time'];
        $response['timer']['current_elapsed'] = $elapsed_seconds;
        $response['timer']['current_elapsed_formatted'] = formatElapsedTime($elapsed_seconds);
    }
    
    // Get updated category counts
    $categorySql = "
        SELECT 
            c.id, 
            c.name, 
            SUM(CASE WHEN t.status = 'running' THEN 1 ELSE 0 END) as running_count,
            SUM(CASE WHEN t.status = 'paused' THEN 1 ELSE 0 END) as paused_count,
            SUM(CASE WHEN t.status = 'idle' THEN 1 ELSE 0 END) as idle_count
        FROM 
            categories c
        LEFT JOIN 
            timers t ON c.id = t.category_id AND (t.is_sticky = 1 OR t.status != 'idle')
        GROUP BY 
            c.id, c.name
        ORDER BY 
            c.name ASC";
    
    $categoryResult = $conn->query($categorySql);
    $categories = [];
    
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'running_count' => (int)$row['running_count'],
            'paused_count' => (int)$row['paused_count'],
            'idle_count' => (int)$row['idle_count']
        ];
    }
    
    $response['categories'] = $categories;
    
    echo json_encode($response);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update sticky status: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?> 