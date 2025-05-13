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
error_log("save_log_note.php was called with data: " . print_r($_POST, true));

// Get database connection
$conn = getDbConnection();

// Get and validate input data
$log_id = isset($_POST['log_id']) ? (int)$_POST['log_id'] : 0;
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

// Validate log_id
if ($log_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid log ID']);
    exit;
}

// Check if log exists
$check_stmt = $conn->prepare("SELECT id FROM timer_logs WHERE id = ?");
$check_stmt->bind_param("i", $log_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Log entry not found']);
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

// Update the note
$sql = "UPDATE timer_logs SET note = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $note, $log_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Note saved successfully',
        'note' => $note
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save note: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?> 