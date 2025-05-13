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

// Check if the timer exists
$stmt = $conn->prepare("SELECT id FROM timers WHERE id = ?");
$stmt->bind_param("i", $timer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Timer not found']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Delete the timer
$stmt = $conn->prepare("DELETE FROM timers WHERE id = ?");
$stmt->bind_param("i", $timer_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Timer deleted successfully',
        'timer_id' => $timer_id
    ]);
} else {
    handleDbError($conn, 'Failed to delete timer');
}

$stmt->close();
$conn->close();
?> 