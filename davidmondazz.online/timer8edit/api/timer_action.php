<?php
// api/timer_action.php (FIXED reset_all and stop_all)

ini_set('display_errors', 0); // Keep 0 for production, check logs
error_reporting(E_ALL);
ini_set('log_errors', 1); // Ensure errors are logged

// Logging Setup
$action_log_file = __DIR__ . '/_timer_action_debug.log';
function log_action($message, $filename) {
    if (empty($filename) || !is_string($filename)) {
        error_log("log_action called with invalid filename."); return;
    }
    file_put_contents($filename, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}
// End Logging Setup

require_once 'db.php'; // Ensure db connection is robust
require_once __DIR__ . '/../includes/level_functions.php'; // For level calculations

// Set header before any potential output/errors
if (!headers_sent()) {
    header('Content-Type: application/json');
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;
$timer_id = isset($input['id']) ? (int)$input['id'] : null;

log_action("Received action: '$action', ID: '$timer_id'", $action_log_file);

$response = ['status' => 'error', 'message' => 'Invalid action or missing data.'];

// Validation
if (!$action || (!in_array($action, ['start', 'stop', 'stop_all', 'reset_all'])) || (!in_array($action, ['stop_all', 'reset_all']) && $timer_id === null)) {
    http_response_code(400);
    log_action("Validation failed: Invalid action/ID combination. Action: $action, ID: $timer_id", $action_log_file);
    echo json_encode($response); // Output JSON error
    exit;
}
// End Validation

try {
    $now_dt = new DateTime();
    $now_str = $now_dt->format('Y-m-d H:i:s');
    $response = ['status' => 'success']; // Default success

    // Fetch Global Settings Needed for Stop/Reset/StopAll
    $difficulty_multiplier = 1.0;
    $levels_config_map = [];
    if (in_array($action, ['stop', 'stop_all', 'reset_all'])) {
        // Get difficulty
        $stmt_difficulty = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'difficulty_multiplier'");
        $stmt_difficulty->execute();
        $difficulty_value = $stmt_difficulty->fetchColumn();
        $difficulty_multiplier = (is_numeric($difficulty_value) && $difficulty_value > 0) ? (float)$difficulty_value : 1.0;
        log_action("Fetched difficulty: $difficulty_multiplier", $action_log_file);

        // Get levels config
        $stmt_levels = $pdo->query("SELECT level, hours_required, rank_name, reward_rate_per_hour FROM levels ORDER BY level ASC");
        if ($stmt_levels === false) throw new Exception("Failed to query level definitions.");
        $levels_data_raw = $stmt_levels->fetchAll(PDO::FETCH_ASSOC);
        if (empty($levels_data_raw)) { throw new Exception("Failed to load level definitions (empty)."); }
        foreach ($levels_data_raw as $level_data) {
            $levels_config_map[$level_data['level']] = $level_data;
        }
        log_action("Fetched " . count($levels_config_map) . " level definitions.", $action_log_file);
    }
    // End Fetch Global Settings

    // Prepare log insert statement (used in stop/stop_all)
    $stmt_insert_log = $pdo->prepare("INSERT INTO timer_logs (timer_id, session_start_time, session_end_time, duration_seconds, earned_amount) VALUES (:timer_id, :start_time, :end_time, :duration, :earned)");


    // --- Action Logic ---

    if ($action === 'start' && $timer_id) {
        // ... (Start logic - verified in previous step, should be OK) ...
        log_action("Attempting START for ID: $timer_id", $action_log_file);
        $stmt = $pdo->prepare("UPDATE timers SET is_running = 1, start_time = :now WHERE id = :id AND is_running = 0");
        $stmt->execute([':now' => $now_str, ':id' => $timer_id]);
        $rowCount = $stmt->rowCount();
        log_action("START query executed for ID: $timer_id. Rows affected: $rowCount", $action_log_file);
        if ($rowCount > 0) {
             $response['startTime'] = $now_str;
        } else {
             $stmt_check = $pdo->prepare("SELECT is_running, start_time FROM timers WHERE id = :id");
             $stmt_check->execute([':id' => $timer_id]);
             $current_state = $stmt_check->fetch();
             if ($current_state && $current_state['is_running'] == 1) {
                 $response['status'] = 'warning'; $response['message'] = 'Timer already running.';
                 $response['startTime'] = $current_state['start_time'];
                 log_action("START warning for ID: $timer_id - Already running.", $action_log_file);
             } else {
                 $response['status'] = 'error'; $response['message'] = 'Timer not found or could not be started.';
                 http_response_code(404);
                 log_action("START error for ID: $timer_id - Not found or failed.", $action_log_file);
             }
        }

    } elseif ($action === 'stop' && $timer_id) {
        log_action("Attempting STOP for ID: $timer_id", $action_log_file);
        $pdo->beginTransaction();
        log_action("STOP ID: $timer_id - Transaction started.", $action_log_file);
        $committed = false;
        try {
            // Get timer data first
            $stmt_timer = $pdo->prepare("SELECT id, start_time, accumulated_seconds, current_level FROM timers WHERE id = :id AND is_running = 1 FOR UPDATE");
            $stmt_timer->execute([':id' => $timer_id]);
            $timer = $stmt_timer->fetch(PDO::FETCH_ASSOC);
            
            if (!$timer) {
                throw new Exception("Timer not found or not running.");
            }

            // Get bank balance
            $stmt_bank = $pdo->prepare("SELECT bank_balance FROM user_progress WHERE id = 1 FOR UPDATE");
            $stmt_bank->execute();
            $bank = $stmt_bank->fetch(PDO::FETCH_ASSOC);
            
            if (!$bank) {
                log_action("Bank record not found, creating it...", $action_log_file);
                $stmt_create_bank = $pdo->prepare("INSERT INTO user_progress (id, bank_balance) VALUES (1, 0)");
                if (!$stmt_create_bank->execute()) {
                    throw new Exception("Failed to initialize bank record.");
                }
                $bank = ['bank_balance' => 0];
            }
            
            $currentBankBalance = (float)$bank['bank_balance'];
            
            // Calculate elapsed time and rewards
            $startTime = new DateTime($timer['start_time']);
            $elapsedSeconds = max(0, $now_dt->getTimestamp() - $startTime->getTimestamp());
            $newAccumulatedSeconds = (float)$timer['accumulated_seconds'] + $elapsedSeconds;
            $totalHours = $newAccumulatedSeconds / 3600.0;
            $currentLevel = (int)$timer['current_level'];
            
            // Calculate new level
            $newLevel = getLevelForHours($totalHours, $levels_config_map, $difficulty_multiplier);
            $levelChanged = ($newLevel > $currentLevel);
            
            // Calculate reward
            $rewardRate = isset($levels_config_map[$currentLevel]) ? (float)$levels_config_map[$currentLevel]['reward_rate_per_hour'] : 0.0;
            $rewardEarned = ($elapsedSeconds / 3600.0) * $rewardRate;
            
            log_action(sprintf("STOP - T(%d): Elapsed: %.2fs, NewAccum: %.2fs, Lvl %d->%d, Reward: %.6f", 
                $timer_id, $elapsedSeconds, $newAccumulatedSeconds, $currentLevel, $newLevel, $rewardEarned), $action_log_file);
            
            // Update timer
            if ($levelChanged) {
                $stmt_update = $pdo->prepare("UPDATE timers SET is_running = 0, start_time = NULL, accumulated_seconds = :accumulated, current_level = :new_level, notified_level = :notified_level WHERE id = :id");
                $updateParams = [
                    ':accumulated' => $newAccumulatedSeconds,
                    ':new_level' => $newLevel,
                    ':notified_level' => $newLevel,
                    ':id' => $timer_id
                ];
            } else {
                $stmt_update = $pdo->prepare("UPDATE timers SET is_running = 0, start_time = NULL, accumulated_seconds = :accumulated WHERE id = :id");
                $updateParams = [
                    ':accumulated' => $newAccumulatedSeconds,
                    ':id' => $timer_id
                ];
            }
            
            log_action("STOP - T($timer_id): Executing timer update with params: " . json_encode($updateParams), $action_log_file);
            if (!$stmt_update->execute($updateParams)) {
                $errorInfo = $stmt_update->errorInfo();
                log_action("!!! STOP - Timer update FAILED. PDO Error: " . print_r($errorInfo, true), $action_log_file);
                throw new Exception("Failed to update timer state.");
            }
            
            // Log the session
            $logParams = [
                ':timer_id' => $timer_id,
                ':start_time' => $timer['start_time'],
                ':end_time' => $now_str,
                ':duration' => $elapsedSeconds,
                ':earned' => $rewardEarned
            ];
            
            log_action("STOP - T($timer_id): Executing log insert. Params: " . json_encode($logParams), $action_log_file);
            if (!$stmt_insert_log->execute($logParams)) {
                $errorInfo = $stmt_insert_log->errorInfo();
                log_action("!!! STOP - Log insert FAILED. PDO Error: " . print_r($errorInfo, true), $action_log_file);
                throw new Exception("Failed to log timer session.");
            }
            
            // Update bank balance
            $newBankBalance = $currentBankBalance + $rewardEarned;
            $stmt_update_bank = $pdo->prepare("UPDATE user_progress SET bank_balance = :balance WHERE id = 1");
            if (!$stmt_update_bank->execute([':balance' => $newBankBalance])) {
                $errorInfo = $stmt_update_bank->errorInfo();
                log_action("!!! STOP - Bank update FAILED. PDO Error: " . print_r($errorInfo, true), $action_log_file);
                throw new Exception("Failed to update bank balance.");
            }
            
            // Commit transaction
            if ($pdo->commit()) {
                $committed = true;
                log_action("STOP finished successfully for timer $timer_id", $action_log_file);
                
                // Prepare success response
                $response['accumulated_seconds'] = $newAccumulatedSeconds;
                $response['bank_balance'] = $newBankBalance;
                if ($levelChanged) {
                    $response['level_up'] = true;
                    $response['new_level'] = $newLevel;
                    $response['new_rank'] = $levels_config_map[$newLevel]['rank_name'] ?? 'Unknown';
                }
            } else {
                throw new Exception("Failed to commit transaction.");
            }
            
        } catch (Exception $e) {
            log_action("!!! STOP ID: $timer_id FAILED (Exception): " . $e->getMessage() . " !!!", $action_log_file);
            if ($pdo->inTransaction() && !$committed) {
                $pdo->rollBack();
            }
            throw $e;
        }

    } elseif ($action === 'stop_all') {
        log_action("Attempting STOP ALL", $action_log_file);
        $pdo->beginTransaction();
        log_action("STOP ALL - Transaction started.", $action_log_file);
        $stopped_timers_data = [];
        $total_reward_earned_cycle = 0;
        $final_bank_balance = 0;
        $committed = false;

        try {
            // Ensure bank record exists
            $stmt_check_bank = $pdo->prepare("SELECT bank_balance FROM user_progress WHERE id = 1 FOR UPDATE");
            $stmt_check_bank->execute();
            $bank = $stmt_check_bank->fetch();
            
            if (!$bank) {
                log_action("Bank record not found for stop_all, creating it...", $action_log_file);
                $stmt_create_bank = $pdo->prepare("INSERT INTO user_progress (id, bank_balance) VALUES (1, 0)");
                if (!$stmt_create_bank->execute()) {
                    $errorInfo = $stmt_create_bank->errorInfo();
                    log_action("!!! Failed to create bank record. PDO Error: " . print_r($errorInfo, true), $action_log_file);
                    throw new Exception("Failed to initialize bank record for stop_all.");
                }
                $bank = ['bank_balance' => 0];
                log_action("Bank record created successfully for stop_all.", $action_log_file);
            }
            
            $currentBankBalance = (float)$bank['bank_balance'];
            $final_bank_balance = $currentBankBalance;
            
            // Select and lock running timers
            $stmt_select = $pdo->query("SELECT id, start_time, accumulated_seconds, current_level FROM timers WHERE is_running = 1 FOR UPDATE");
            if ($stmt_select === false) throw new Exception("Failed to query running timers for stop_all.");
            $running_timers = $stmt_select->fetchAll();
            log_action("STOP ALL found ".count($running_timers)." running timers.", $action_log_file);

            // Prepare update statements outside the loop
            $stmt_update_timer_no_level = $pdo->prepare("UPDATE timers SET is_running = 0, start_time = NULL, accumulated_seconds = :accumulated WHERE id = :id");
            $stmt_update_timer_level = $pdo->prepare("UPDATE timers SET is_running = 0, start_time = NULL, accumulated_seconds = :accumulated, current_level = :new_level, notified_level = :notified_level WHERE id = :id");

            foreach ($running_timers as $timer) {
                $timer_id_loop = $timer['id'];
                // Calculations
                $startTime = new DateTime($timer['start_time']);
                $elapsedSeconds = max(0, $now_dt->getTimestamp() - $startTime->getTimestamp());
                $newAccumulatedSeconds = (float)$timer['accumulated_seconds'] + $elapsedSeconds;
                $totalHours = $newAccumulatedSeconds / 3600.0;
                $currentLevel = (int)$timer['current_level'];
                $newLevel = getLevelForHours($totalHours, $levels_config_map, $difficulty_multiplier);
                $levelChanged = ($newLevel > $currentLevel);
                $rewardRate = isset($levels_config_map[$currentLevel]) ? (float)$levels_config_map[$currentLevel]['reward_rate_per_hour'] : 0.0;
                $rewardEarned = ($elapsedSeconds / 3600.0) * $rewardRate;
                $total_reward_earned_cycle += $rewardEarned;
                $sessionStartTimeStr = $startTime->format('Y-m-d H:i:s');

                log_action(sprintf("STOP ALL - T(%d): Elapsed: %.2fs, NewAccum: %.2fs, Lvl %d->%d, Reward: %.6f", $timer_id_loop, $elapsedSeconds, $newAccumulatedSeconds, $currentLevel, $newLevel, $rewardEarned), $action_log_file);

                // Timer Update
                $params = [
                    ':accumulated' => $newAccumulatedSeconds,
                    ':id' => $timer_id_loop
                ];

                if ($levelChanged) {
                    $params[':new_level'] = $newLevel;
                    $params[':notified_level'] = $newLevel;
                    $timerUpdateSuccess = $stmt_update_timer_level->execute($params);
                } else {
                    $timerUpdateSuccess = $stmt_update_timer_no_level->execute($params);
                }

                if (!$timerUpdateSuccess) {
                    $errorInfo = $levelChanged ? $stmt_update_timer_level->errorInfo() : $stmt_update_timer_no_level->errorInfo();
                    log_action("!!! STOP ALL - Timer update FAILED for ID $timer_id_loop. PDO Error: " . print_r($errorInfo, true), $action_log_file);
                    throw new Exception("Failed to update timer state for ID $timer_id_loop during stop_all.");
                }

                // Log the Session
                $logParams = [
                    ':timer_id' => $timer_id_loop,
                    ':start_time' => $sessionStartTimeStr,
                    ':end_time' => $now_str,
                    ':duration' => $elapsedSeconds,
                    ':earned' => $rewardEarned
                ];
                
                if (!$stmt_insert_log->execute($logParams)) {
                    $errorInfo = $stmt_insert_log->errorInfo();
                    log_action("!!! WARNING: Failed to insert timer log for ID $timer_id_loop during stop_all. Error: ".print_r($errorInfo, true), $action_log_file);
                    throw new Exception("Failed to insert timer log for ID $timer_id_loop.");
                }

                // Store data for response
                $stopped_data = [
                    'id' => $timer_id_loop,
                    'accumulated_seconds' => $newAccumulatedSeconds
                ];
                if ($levelChanged) {
                    $stopped_data['level_up'] = true;
                    $stopped_data['new_level'] = $newLevel;
                    $stopped_data['new_rank'] = $levels_config_map[$newLevel]['rank_name'] ?? 'Unknown';
                }
                $stopped_timers_data[] = $stopped_data;
            }

            // Bank Update
            $final_bank_balance = $currentBankBalance + $total_reward_earned_cycle;
            $stmt_update_bank = $pdo->prepare("UPDATE user_progress SET bank_balance = :balance WHERE id = 1");
            if (!$stmt_update_bank->execute([':balance' => $final_bank_balance])) {
                throw new Exception("Failed to update bank balance during stop_all.");
            }

            // Commit transaction
            if ($pdo->commit()) {
                $committed = true;
                $response['stopped_timers'] = $stopped_timers_data;
                $response['bank_balance'] = $final_bank_balance;
                $response['total_reward_earned_cycle'] = $total_reward_earned_cycle;
            } else {
                throw new Exception("Failed to commit transaction during stop_all.");
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction() && !$committed) {
                $pdo->rollBack();
            }
            throw $e;
        }

    } elseif ($action === 'reset_all') {
        // --- RESET ALL ---
        log_action("Attempting RESET ALL", $action_log_file);
        $pdo->beginTransaction(); // Start transaction
        log_action("RESET ALL - Transaction started.", $action_log_file);
        $reset_timers_data = [];
        $committed = false; // Commit flag

        try {
            // First, get a count of all timers for validation
            log_action("RESET ALL - Counting total timers...", $action_log_file);
            $stmt_count = $pdo->query("SELECT COUNT(*) FROM timers");
            if ($stmt_count === false) {
                $errorInfo = $pdo->errorInfo();
                log_action("!!! RESET ALL - Failed to count timers. PDO Error: " . print_r($errorInfo, true), $action_log_file);
                throw new Exception("Failed to count timers during reset_all. DB Error: " . ($errorInfo[2] ?? 'Unknown'));
            }
            $total_timers = (int)$stmt_count->fetchColumn();
            log_action("RESET ALL - Found $total_timers timers to reset.", $action_log_file);

            // Reset all timers to initial state
            $sql_reset_timers = "
                UPDATE timers 
                SET accumulated_seconds = 0,
                    is_running = 0,
                    start_time = NULL,
                    current_level = 1,
                    notified_level = 1
            ";
            $stmt_update = $pdo->prepare($sql_reset_timers);
            
            // Execute the reset
            log_action("RESET ALL - Executing timer reset update...", $action_log_file);
            $updateSuccess = $stmt_update->execute();
            log_action("RESET ALL - Timer reset execute() result: " . ($updateSuccess ? 'TRUE' : 'FALSE'), $action_log_file);

            if (!$updateSuccess) {
                $errorInfo = $stmt_update->errorInfo();
                log_action("!!! RESET ALL - Timer reset FAILED. PDO Error: " . print_r($errorInfo, true), $action_log_file);
                throw new Exception("Failed to reset timers. DB Error: " . ($errorInfo[2] ?? 'Unknown'));
            }

            $rowCount = $stmt_update->rowCount();
            log_action("RESET ALL - Timers reset query executed. Rows affected: $rowCount", $action_log_file);

            // Reset bank balance to 0
            log_action("RESET ALL - Resetting bank balance...", $action_log_file);
            $stmt_reset_bank = $pdo->prepare("UPDATE user_progress SET bank_balance = 0 WHERE id = 1");
            $resetBankSuccess = $stmt_reset_bank->execute();
            log_action("RESET ALL - Bank reset execute() result: " . ($resetBankSuccess ? 'TRUE' : 'FALSE'), $action_log_file);
            
            if (!$resetBankSuccess) {
                $errorInfo = $stmt_reset_bank->errorInfo();
                log_action("!!! RESET ALL - Bank reset FAILED. PDO Error: " . print_r($errorInfo, true), $action_log_file);
                throw new Exception("Failed to reset bank balance. DB Error: " . ($errorInfo[2] ?? 'Unknown'));
            }

            // Clear all timer logs
            log_action("RESET ALL - Clearing timer logs...", $action_log_file);
            $stmt_clear_logs = $pdo->prepare("DELETE FROM timer_logs");
            $clearLogsSuccess = $stmt_clear_logs->execute();
            log_action("RESET ALL - Clear logs execute() result: " . ($clearLogsSuccess ? 'TRUE' : 'FALSE'), $action_log_file);
            
            if (!$clearLogsSuccess) {
                $errorInfo = $stmt_clear_logs->errorInfo();
                log_action("!!! RESET ALL - Failed to clear timer logs. PDO Error: " . print_r($errorInfo, true), $action_log_file);
                log_action("!!! WARNING: Timer logs could not be cleared but continuing with reset", $action_log_file);
                $response['warning'] = "Timer logs could not be cleared.";
            }

            // Get all timer IDs for response
            $stmt_select_ids = $pdo->query("SELECT id FROM timers ORDER BY id ASC");
            if ($stmt_select_ids === false) {
                $errorInfo = $pdo->errorInfo();
                log_action("!!! RESET ALL - Failed to query timer IDs post-reset. PDO Error: " . print_r($errorInfo, true), $action_log_file);
                throw new Exception("Failed to query timer IDs after reset.");
            }
            
            $all_timer_ids = $stmt_select_ids->fetchAll(PDO::FETCH_COLUMN);
            foreach ($all_timer_ids as $id) {
                $reset_timers_data[] = [
                    'id' => $id,
                    'accumulated_seconds' => 0,
                    'current_level' => 1,
                    'is_running' => 0
                ];
            }

            // Commit transaction
            log_action("RESET ALL - Attempting commit...", $action_log_file);
            if ($pdo->commit()) {
                $committed = true;
                log_action("RESET ALL finished and committed successfully.", $action_log_file);
                
                // Prepare success response
                $response['reset_timers'] = $reset_timers_data;
                $response['bank_balance'] = 0;
                
                if (isset($response['warning'])) {
                    log_action("RESET ALL completed with warnings: " . $response['warning'], $action_log_file);
                }
            } else {
                throw new Exception("Failed to commit transaction.");
            }

        } catch (Exception $e) {
            log_action("!!! RESET ALL FAILED (Exception): " . $e->getMessage() . " !!!", $action_log_file);
            if ($pdo->inTransaction() && !$committed) {
                log_action("Rolling back RESET ALL transaction due to exception.", $action_log_file);
                $pdo->rollBack();
            }
            throw $e;
        }

    } else {
         log_action("Unknown action received: '$action'", $action_log_file);
         $response = ['status' => 'error', 'message' => 'Unknown action.']; http_response_code(400);
    }

} catch (Exception $e) {
    log_action("!!! ACTION FAILED (Outer Catch): " . $e->getMessage() . " !!!", $action_log_file);
    // Ensure response status reflects the error if not already set
    if ($response['status'] !== 'error' && $response['status'] !== 'warning') {
         $response['status'] = 'error';
    }
     // Add exception message if not already present or more specific
     $response['message'] = 'Action failed: ' . $e->getMessage();
    http_response_code(500); // Set 500 for server errors
    error_log("timer_action.php Error: " . $e->getMessage()); // Log to standard PHP error log
}

// Final Output
if (!headers_sent()) {
    header('Content-Type: application/json');
}
log_action("Sending final response: " . json_encode($response), $action_log_file);
echo json_encode($response);
exit; // Explicitly exit
?>