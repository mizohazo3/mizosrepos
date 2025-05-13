<?php
require_once 'db.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    // Get user_session_id from input
    $user_session_id = $input['user_session_id'] ?? null;

    if (!$user_session_id) {
         http_response_code(400);
         echo json_encode(['status' => 'error', 'message' => 'User session ID is required']);
         exit;
    }

    // Delete all pending items from note for this user session
    $stmt = $pdo->prepare("DELETE FROM note_items WHERE user_session_id = :user_session_id AND status = 'pending'");
    if (!$stmt->execute([':user_session_id' => $user_session_id])) {
        throw new Exception('Failed to clear note');
    }

    // We don't necessarily need to throw an error if rowCount is 0,
    // as the user might just click clear when it's already empty.
    // if ($stmt->rowCount() === 0) {
    //     throw new Exception('Note is already empty');
    // }

    echo json_encode([
        'status' => 'success',
        'message' => 'Note cleared successfully',
        'cleared_count' => $stmt->rowCount() // Optionally return count of cleared items
    ]);

} catch (Exception $e) {
    error_log('Clear note error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}