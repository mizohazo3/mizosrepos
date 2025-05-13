<?php
require_once 'db_connect.php';
date_default_timezone_set("Africa/Cairo");

// Assuming you have a user session or identifier
$user_id = 1; // Replace with actual user identifier

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$paused_time = isset($input['paused_time']) ? intval($input['paused_time']) : 0;

try {
    // Check if timer data exists for the user
    $stmt = $db->prepare("SELECT id FROM timers WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Update existing timer data
        $stmt = $db->prepare("UPDATE timers SET paused_time = :paused_time WHERE user_id = :user_id");
    } else {
        // Insert new timer data
        $stmt = $db->prepare("INSERT INTO timers (user_id, paused_time) VALUES (:user_id, :paused_time)");
    }

    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':paused_time', $paused_time, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
