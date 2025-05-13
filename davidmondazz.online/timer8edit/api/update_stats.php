<?php
// update_stats.php - Effectively Disabled for time/reward calculation

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

$log_file = __DIR__ . '/_update_stats_debug.log';
// ... logging setup ...
function log_message($message, $log_file = null) { /* ... */ }

log_message("\n=== Update Stats Run (Offline Mode - Logic Disabled) ===");

require_once 'db.php';
header('Content-Type: application/json'); // Still good practice

$response = [
    'status' => 'success',
    'message' => 'Update stats script ran (offline mode - no time/reward calculation performed).',
    'bank_balance' => null, // Indicate no update performed here
    'timers_levelled_data' => [],
    'timers' => [],
    'difficulty_multiplier' => null,
    'debug' => null
];

try {
    // You *could* add a sanity check here for levels if desired,
    // but the primary logic is moved to timer_action.php.
    // Example: Fetch timers, calculate total hours, check if current_level matches,
    // and maybe update notified_level if needed. But avoid updating bank/accumulated time.

    // Fetch current bank balance just to return it if needed
    $stmt_progress = $pdo->query("SELECT bank_balance FROM user_progress WHERE id = 1");
    $progress = $stmt_progress->fetch();
    $response['bank_balance'] = $progress ? (float)$progress['bank_balance'] : 0.00;

    log_message("Script executed successfully (no core actions taken).");

} catch (Exception $e) {
    log_message("!!! EXCEPTION CAUGHT (Offline Mode): " . $e->getMessage() . " !!!");
    http_response_code(500);
    $response['status'] = 'error';
    $response['message'] = 'Error during update_stats run (offline mode): ' . $e->getMessage();
}

log_message("Script finished (Offline Mode).");
echo json_encode($response);
exit;
?>