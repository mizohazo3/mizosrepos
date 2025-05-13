<?php
// api/get_data.php

ini_set('display_errors', 0); // Keep errors hidden from users
ini_set('log_errors', 1);     // Log errors to the server log
error_reporting(E_ALL);

// Proper logging function implementation
function log_action($message, $log_file) {
    $timestamp = date('[H:i:s] ');
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    file_put_contents($log_file, $timestamp . $message . "\n", FILE_APPEND);
}

require_once 'db.php'; // Ensure this path is correct
header('Content-Type: application/json');

$response = ['status' => 'error'];
$action_log_file = __DIR__ . '/../logs/get_data.log';

try {
    // --- Settings ---
    $stmt_difficulty = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'difficulty_multiplier'");
    $stmt_difficulty->execute();
    $difficulty_value = $stmt_difficulty->fetchColumn();
    $difficulty_multiplier = (is_numeric($difficulty_value) && $difficulty_value > 0) ? (float)$difficulty_value : 1.0;

    // --- Levels (Map) ---
    log_action("get_data.php: Attempting to query levels table...", $action_log_file); // Add log
    $stmt_all_levels = $pdo->query("SELECT level, hours_required, rank_name, reward_rate_per_hour FROM levels ORDER BY level ASC");

    // *** ADDED CHECK ***
    if ($stmt_all_levels === false) {
        $errorInfo = $pdo->errorInfo();
        log_action("get_data.php: Failed to execute levels query. PDO Error: " . print_r($errorInfo, true), $action_log_file);
        throw new Exception("Database query error fetching levels. Check server logs."); // More specific error
    }
    // *** END ADDED CHECK ***

    $levels_config_map = $stmt_all_levels->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

    // Check if fetchAll succeeded AND returned data
    if (empty($levels_config_map)) {
         // Check if the query actually returned rows but fetchAll failed (less likely with FETCH_UNIQUE)
         $rowCount = $stmt_all_levels->rowCount(); // Get row count AFTER fetchAll might be unreliable on some drivers, but worth checking
         log_action("get_data.php: Levels query succeeded but returned empty results (rowCount attempted: $rowCount). Is the levels table populated?", $action_log_file);
         throw new Exception("Failed to load base level definitions (Levels table might be empty)."); // Refined message
    }
    log_action("get_data.php: Successfully fetched " . count($levels_config_map) . " level definitions.", $action_log_file); // Add log


    // --- Timers (with Total Earned) ---
    log_action("get_data.php: Attempting to query timers table...", $action_log_file); // Add log
    $stmt_timers = $pdo->query("
        SELECT
            t.id, t.name, t.accumulated_seconds, t.start_time, t.is_running,
            t.current_level, t.notified_level, l_curr.rank_name, l_curr.reward_rate_per_hour,
            COALESCE(tl.total_earned, 0.000000) AS total_earned
        FROM timers t
        LEFT JOIN levels l_curr ON t.current_level = l_curr.level -- Use LEFT JOIN in case level is somehow invalid
        LEFT JOIN (
            SELECT timer_id, SUM(earned_amount) as total_earned
            FROM timer_logs
            GROUP BY timer_id
        ) tl ON t.id = tl.timer_id
        ORDER BY t.created_at ASC
    ");
    // *** ADDED CHECK ***
     if ($stmt_timers === false) {
        $errorInfo = $pdo->errorInfo();
        log_action("get_data.php: Failed to execute timers query. PDO Error: " . print_r($errorInfo, true), $action_log_file);
        throw new Exception("Database query error fetching timers. Check server logs."); // More specific error
    }
    // *** END ADDED CHECK ***
    $timers_raw = $stmt_timers->fetchAll(PDO::FETCH_ASSOC);
    log_action("get_data.php: Successfully fetched " . count($timers_raw) . " timers.", $action_log_file); // Add log


    // --- User Progress ---
    $stmt_progress = $pdo->query("SELECT bank_balance FROM user_progress WHERE id = 1");
     // *** ADDED CHECK ***
     if ($stmt_progress === false) {
        $errorInfo = $pdo->errorInfo();
        log_action("get_data.php: Failed to execute user_progress query. PDO Error: " . print_r($errorInfo, true), $action_log_file);
        throw new Exception("Database query error fetching user progress. Check server logs."); // More specific error
    }
     // *** END ADDED CHECK ***
    $progress = $stmt_progress->fetch();
    $bank_balance = $progress ? (float)$progress['bank_balance'] : 0.00;


    // --- Prepare Response ---
    $response = [
        'status' => 'success',
        'timers' => $timers_raw,
        'bank_balance' => $bank_balance,
        'difficulty_multiplier' => $difficulty_multiplier,
        'levels_config' => $levels_config_map
    ];

} catch (Exception $e) {
    http_response_code(500);
    // Log the specific exception message caught
    log_action("get_data.php CAUGHT ERROR: " . $e->getMessage(), $action_log_file);
    $response['message'] = 'Error fetching data: ' . $e->getMessage();
    // Ensure consistent error structure
    $response['timers'] = [];
    $response['bank_balance'] = null;
    $response['difficulty_multiplier'] = null;
    $response['levels_config'] = null;
}

echo json_encode($response);
?>