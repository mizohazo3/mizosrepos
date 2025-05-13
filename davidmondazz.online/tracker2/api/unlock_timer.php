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

if ($timer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid timer ID is required']);
    exit;
}

// Check if the timer exists and get its current manage_status
$stmt = $conn->prepare("SELECT id, manage_status FROM timers WHERE id = ?");
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

// Check if the timer is already unlocked
if ($timer['manage_status'] === NULL) {
    echo json_encode(['success' => false, 'message' => 'Timer is already unlocked']);
    $conn->close();
    exit;
}

// Update the timer's manage_status to NULL (unlocked)
$stmt = $conn->prepare("UPDATE timers SET manage_status = NULL WHERE id = ?");
$stmt->bind_param("i", $timer_id);

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
        'message' => 'Timer unlocked successfully',
        'timer' => [
            'id' => $timer['id'],
            'name' => $timer['name'],
            'category_id' => $timer['category_id'],
            'category_name' => $timer['category_name'],
            'status' => $timer['status'],
            'manage_status' => $timer['manage_status'],
            'total_time' => (int)$timer['total_time'],
            'total_time_formatted' => formatTime((int)$timer['total_time']),
            'updated_at' => $timer['updated_at']
        ]
    ];
    
    echo json_encode($response);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to unlock timer: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?> 