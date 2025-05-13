<?php

include_once '../timezone_config.php';
// api/get_data.php

ini_set('display_errors', 0); // Keep errors hidden from users
ini_set('log_errors', 1);     // Log errors to the server log
error_reporting(E_ALL);

// Proper logging function implementation
function log_action($message, $log_file) {
    $timestamp = date('[H:i:s] ');
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        // Attempt to create directory recursively
        if (!mkdir($log_dir, 0777, true) && !is_dir($log_dir)) {
             // Log failure if directory creation fails and directory doesn't exist
             return; // Stop logging if directory cannot be created
        }
    }
    // Check if file is writable, log error if not
    if (!is_writable($log_dir)) {
        return;
    }
    file_put_contents($log_file, $timestamp . $message . "\n", FILE_APPEND);
}


require_once 'db.php'; // Ensure this path is correct
header('Content-Type: application/json');

$response = ['status' => 'error'];
$action_log_file = __DIR__ . '/../logs/get_data.log'; // Ensure this directory is writable by the web server

// --- NEW: Check for filter and count_only ---
$filter_mode = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // Default to 'all' if not specified
$count_only = isset($_GET['count_only']) && $_GET['count_only'] == '1';

try {
    // If count_only is true, just get the running timer count for more efficient updates
    if ($count_only) {
        $stmt_count = $pdo->query("SELECT COUNT(*) as count FROM timers WHERE is_running = 1");
        if ($stmt_count === false) {
            throw new Exception("Failed to count running timers");
        }
        $running_count = $stmt_count->fetchColumn();
        
        $response = [
            'status' => 'success',
            'running_count' => (int)$running_count
        ];
        
        echo json_encode($response);
        exit;
    }
    
    // --- Settings ---
    $stmt_difficulty = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'difficulty_multiplier'");
    $stmt_difficulty->execute();
    $difficulty_value = $stmt_difficulty->fetchColumn();
    $difficulty_multiplier = (is_numeric($difficulty_value) && $difficulty_value > 0) ? (float)$difficulty_value : 1.0;

    // --- Levels (Map) ---
    log_action("get_data.php: Attempting to query levels table...", $action_log_file);
    $stmt_all_levels = $pdo->query("SELECT level, hours_required, rank_name, reward_rate_per_hour FROM levels ORDER BY level ASC");

    if ($stmt_all_levels === false) {
        $errorInfo = $pdo->errorInfo();
        throw new Exception("Database query error fetching levels. Check server logs.");
    }

    $levels_config_map = $stmt_all_levels->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

    if (empty($levels_config_map)) {
         $rowCount = $stmt_all_levels->rowCount();
         log_action("get_data.php: Levels query succeeded but returned empty results (rowCount attempted: $rowCount). Is the levels table populated?", $action_log_file);
         throw new Exception("Failed to load base level definitions (Levels table might be empty).");
    }
    log_action("get_data.php: Successfully fetched " . count($levels_config_map) . " level definitions.", $action_log_file);


    // --- Timers (Modify Query based on filter) ---
    log_action("get_data.php: Attempting to query timers table (Filter: $filter_mode)...", $action_log_file);

    // Base query parts
    $sql_select = "
        SELECT
            t.id, t.name, t.accumulated_seconds, t.start_time, t.is_running,
            t.current_level, t.notified_level, t.is_pinned, l_curr.rank_name, l_curr.reward_rate_per_hour,
            COALESCE(tl.total_earned, 0.000000) AS total_earned
        FROM timers t
        LEFT JOIN levels l_curr ON t.current_level = l_curr.level
        LEFT JOIN (
            SELECT timer_id, SUM(earned_amount) as total_earned
            FROM timer_logs
            GROUP BY timer_id
        ) tl ON t.id = tl.timer_id
    ";
    $sql_where = ""; // Initialize WHERE clause
    $sql_order = "
        ORDER BY
            t.is_pinned DESC,
            t.is_running DESC,
            t.accumulated_seconds DESC
    ";
    $params = []; // Initialize parameters for prepared statement

    // --- Apply Filter ---
    if ($filter_mode === 'pinned') {
        $sql_where = " WHERE t.is_pinned = 1 ";
        log_action("get_data.php: Applying 'pinned' filter.", $action_log_file);
    }

    // Combine query parts
    $final_sql = $sql_select . $sql_where . $sql_order;

    // Prepare and execute
    $stmt_timers = $pdo->prepare($final_sql);
    $stmt_timers->execute($params);

    if ($stmt_timers === false) {
        $errorInfo = $pdo->errorInfo();
        throw new Exception("Database query error fetching timers. Check server logs.");
    }

    $timers_raw = $stmt_timers->fetchAll(PDO::FETCH_ASSOC);
    log_action("get_data.php: Successfully fetched " . count($timers_raw) . " timers (Filter: $filter_mode).", $action_log_file);

    // --- Transform timers array into object with IDs as keys ---
    $timers_keyed = [];
    foreach ($timers_raw as $timer) {
        // Ensure ID is a string
        $timer_id = (string)$timer['id'];
        
        // Ensure numeric types are properly cast
        $timer['is_running'] = (int)$timer['is_running']; // Keep as int for JSON
        $timer['is_pinned'] = (int)$timer['is_pinned']; // Keep as int for JSON
        $timer['accumulated_seconds'] = (float)$timer['accumulated_seconds'];
        $timer['current_level'] = (int)$timer['current_level'];
        $timer['total_earned'] = (float)$timer['total_earned'];
        
        // Use the timer's ID as the key
        $timers_keyed[$timer_id] = $timer;
    }

    // --- Calculate Bank Balance ---
    $bank_balance = calculateDynamicBalance($pdo);
    log_action("get_data.php: Calculated dynamic balance: " . $bank_balance, $action_log_file);

    // --- Also include running timer count for convenience ---
    $running_count = 0;
    foreach ($timers_keyed as $timer) {
        if ($timer['is_running']) {
            $running_count++;
        }
    }

    // --- Prepare Response ---
    $response = [
        'status' => 'success',
        'timers' => $timers_keyed, // Now using the keyed object instead of raw array
        'bank_balance' => $bank_balance,
        'difficulty_multiplier' => $difficulty_multiplier,
        'levels' => $levels_config_map, // Changed from levels_config to match expected structure
        'running_count' => $running_count // Include running timer count in response
    ];

    log_action("get_data.php: Successfully prepared response with " . count($timers_keyed) . " keyed timers.", $action_log_file);

} catch (Exception $e) {
    http_response_code(500);
    log_action("get_data.php CAUGHT ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine(), $action_log_file); // More detailed logging
    $response['message'] = 'Error fetching data. Please check server logs for details.'; // Generic message for user
    // Ensure consistent error structure
    $response['timers'] = [];
    $response['bank_balance'] = null;
    $response['difficulty_multiplier'] = null;
    $response['levels'] = null; // Changed from levels_config to match success case
}

// Ensure numeric values are properly encoded
echo json_encode($response, JSON_NUMERIC_CHECK);
?>