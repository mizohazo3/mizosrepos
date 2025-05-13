<?php
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // Log the received data for debugging
    error_log("Received data in add_to_note.php: " . print_r($data, true));

    // Check if required data is present (basic check)
    if (isset($data['item_id']) && isset($data['user_session_id'])) {
        // In a real implementation, you would add this to the database
        // For now, just return success to see if the frontend receives it
        $response = ['status' => 'success', 'message' => 'Item data received successfully.'];
    } else {
        $response = ['status' => 'error', 'message' => 'Missing item_id or user_session_id.'];
    }
}

echo json_encode($response);
?>