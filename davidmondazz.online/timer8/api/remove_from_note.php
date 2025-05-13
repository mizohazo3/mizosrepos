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
    
    // Validate required fields
    if (!isset($input['item_id']) || !isset($input['user_session_id'])) {
        throw new Exception('Missing required fields: item_id and user_session_id are required');
    }

    $item_id = (int)$input['item_id'];
    $user_session_id = $input['user_session_id'];

    // Remove the most recent pending item with matching item_id and user_session_id
    $stmt = $pdo->prepare("
        DELETE FROM note_items 
        WHERE item_id = :item_id 
        AND user_session_id = :user_session_id 
        AND status = 'pending' 
        ORDER BY added_at DESC 
        LIMIT 1
    ");

    if (!$stmt->execute([
        ':item_id' => $item_id,
        ':user_session_id' => $user_session_id
    ])) {
        throw new Exception('Failed to remove item from note');
    }

    if ($stmt->rowCount() === 0) {
        throw new Exception('Item not found in note');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Item removed from note successfully'
    ]);

} catch (Exception $e) {
    error_log('Remove from note error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}