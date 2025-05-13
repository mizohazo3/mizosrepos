<?php

include_once '../timezone_config.php';
// api/view_timer_logs.php

require_once 'db.php'; // Include database connection

header('Content-Type: application/json');

try {
    // Prepare and execute the select statement
    $stmt = $pdo->query("SELECT * FROM timer_logs ORDER BY session_end_time DESC");

    // Fetch all results
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Output the results as JSON
    echo json_encode(['status' => 'success', 'logs' => $logs]);

} catch (PDOException $e) {
    error_log('Error in view_timer_logs.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error fetching timer logs: ' . $e->getMessage()]);
}
?>