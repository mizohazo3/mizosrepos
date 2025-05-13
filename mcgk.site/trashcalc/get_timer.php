<?php
require_once 'db_connect.php';
date_default_timezone_set("Africa/Cairo");

// Assuming you have a user session or identifier
$user_id = 1; // Replace with actual user identifier

try {
    $stmt = $db->prepare("SELECT paused_time FROM timers WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(['status' => 'success', 'paused_time' => $result['paused_time']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Timer data not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
